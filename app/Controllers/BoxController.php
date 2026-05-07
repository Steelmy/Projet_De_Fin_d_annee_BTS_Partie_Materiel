<?php

require_once __DIR__ . '/../Models/Box.php';
require_once __DIR__ . '/../Models/Item.php';

/**
 * Contrôleur des opérations sur les caisses (CRUD + gestion du contenu).
 *
 * Les opérations create/update/delete s'exécutent sous transaction
 * pour conserver la cohérence entre `caisses` et `objets.Caisse_id`.
 */
class BoxController
{
    /** @var Box Modèle d'accès aux caisses. */
    private Box $boxModel;

    /** @var Item Modèle d'accès aux objets. */
    private Item $itemModel;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->boxModel = new Box($conn);
        $this->itemModel = new Item($conn);
    }

    /**
     * Liste toutes les caisses, chacune enrichie de son contenu et du nombre d'objets.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function index(): void
    {
        try {
            $caisses = $this->boxModel->getAll();

            foreach ($caisses as &$caisse) {
                $caisse['Contenu'] = $this->itemModel->getByCaisseId($caisse['id']);
                $caisse['nombre_objets'] = count($caisse['Contenu']);
            }

            ApiResponse::success([
                'data' => $caisses,
                'total' => count($caisses)
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Récupère le détail complet d'une caisse (contenu + utilisateur emprunteur).
     * Entrée GET : `nom` ou `id` (au moins un requis).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function show(): void
    {
        try {
            $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if (empty($nom) && $id === 0) {
                ApiResponse::error('Nom ou ID de la caisse requis');
            }

            $caisse = $id > 0
                ? $this->boxModel->getById($id)
                : $this->boxModel->getByName($nom);

            if (!$caisse) {
                ApiResponse::error('Caisse non trouvée');
            }

            $contenu = $this->itemModel->getByCaisseId($caisse['id']);

            $response = [
                'caisse' => [
                    'id' => $caisse['id'],
                    'nom' => $caisse['Nom'],
                    'contenu' => $contenu,
                    'nombre_objets' => count($contenu),
                    'etat' => $caisse['Etat'],
                    'created_at' => $caisse['created_at'],
                    'updated_at' => $caisse['updated_at'],
                    'emprunteur_id' => $caisse['Emprunteur_id']
                ]
            ];

            if ($caisse['Etat'] !== 'disponible' && $caisse['Prénom']) {
                $response['caisse']['utilisateur'] = [
                    'id' => $caisse['Emprunteur_id'],
                    'nom_complet' => $caisse['Prénom'] . ' ' . $caisse['user_nom']
                ];
            }

            ApiResponse::success($response);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Crée une caisse et y affecte les objets fournis.
     * Entrée POST : `nom`, `objets_ids` (JSON list d'IDs).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function store(): void
    {
        try {
            $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
            $objetsIds = isset($_POST['objets_ids']) ? json_decode($_POST['objets_ids'], true) : [];

            if (empty($nom)) {
                ApiResponse::error('Le nom de la caisse est requis');
            }
            if (!is_array($objetsIds)) {
                ApiResponse::error('Format des objets invalide');
            }
            if ($this->boxModel->nameExists($nom)) {
                ApiResponse::error('Une caisse avec ce nom existe déjà');
            }

            $this->boxModel->beginTransaction();

            $caisseId = $this->boxModel->create($nom);

            foreach ($objetsIds as $objetId) {
                $this->itemModel->assignToBox($objetId, $caisseId);
            }

            $this->boxModel->commit();

            $objets = $this->itemModel->getByCaisseId($caisseId);


            ApiResponse::success([
                'caisse' => [
                    'id' => $caisseId,
                    'nom' => $nom,
                    'contenu' => $objets,
                    'nombre_objets' => count($objets)
                ]
            ], 'Caisse ajoutée avec succès');
        } catch (PDOException $e) {
            $this->boxModel->rollBack();
            ApiResponse::exception($e);
        }
    }

    /**
     * Met à jour une caisse : nom, état, emprunteur et/ou contenu.
     * Si un nouveau contenu est fourni, l'ancien est libéré et remplacé intégralement.
     * Entrée POST : `id` ou `nom`, plus `nouveau_nom`/`etat`/`emprunteur_id`/`objets_ids` (tous optionnels).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function update(): void
    {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
            $nouveauNom = isset($_POST['nouveau_nom']) ? trim($_POST['nouveau_nom']) : '';
            $objetsIds = isset($_POST['objets_ids']) ? json_decode($_POST['objets_ids'], true) : null;
            $etat = isset($_POST['etat']) ? trim($_POST['etat']) : null;
            $emprunteurId = isset($_POST['emprunteur_id']) ? intval($_POST['emprunteur_id']) : null;

            if ($id === 0 && empty($nom)) {
                ApiResponse::error('ID ou nom de la caisse requis');
            }

            $caisse = $id > 0
                ? $this->boxModel->findById($id)
                : $this->boxModel->findByName($nom);

            if (!$caisse) {
                ApiResponse::error('Caisse non trouvée');
            }

            $this->boxModel->beginTransaction();

            $fields = [];

            if (!empty($nouveauNom) && $nouveauNom !== $caisse['Nom']) {
                $fields['Nom'] = $nouveauNom;
            }

            if ($etat !== null) {
                $fields['Etat'] = $etat;

                if ($etat === 'disponible') {
                    $fields['Emprunteur_id'] = null;
                } else {
                    if ($emprunteurId <= 0) {
                        $this->boxModel->rollBack();
                        ApiResponse::error("Un utilisateur doit être sélectionné pour une caisse réservée ou empruntée");
                    }
                    $fields['Emprunteur_id'] = $emprunteurId;
                }
            }

            if ($objetsIds !== null && is_array($objetsIds)) {
                $this->itemModel->freeFromBox($caisse['id']);
                foreach ($objetsIds as $objetId) {
                    $this->itemModel->assignToBox($objetId, $caisse['id']);
                }
            }

            if (!empty($fields)) {
                $this->boxModel->update($caisse['id'], $fields);
            }

            $this->boxModel->commit();


            ApiResponse::success([
                'updated' => ['id' => $caisse['id'], 'nom' => $nouveauNom ?: $caisse['Nom']]
            ], 'Caisse modifiée avec succès');
        } catch (PDOException $e) {
            $this->boxModel->rollBack();
            ApiResponse::exception($e);
        }
    }

    /**
     * Supprime une caisse et libère son contenu.
     * Entrée POST : `id` ou `nom` (au moins un requis).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function destroy(): void
    {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';

            if ($id === 0 && empty($nom)) {
                ApiResponse::error('ID ou nom de la caisse requis');
            }

            $caisse = $id > 0
                ? $this->boxModel->findById($id)
                : $this->boxModel->findByName($nom);

            if (!$caisse) {
                ApiResponse::error('Caisse non trouvée');
            }

            $this->boxModel->beginTransaction();

            $this->itemModel->freeFromBox($caisse['id']);
            $this->boxModel->delete($caisse['id']);

            $this->boxModel->commit();


            ApiResponse::success([
                'deleted' => ['id' => $caisse['id'], 'nom' => $caisse['Nom']]
            ], 'Caisse supprimée avec succès');
        } catch (PDOException $e) {
            $this->boxModel->rollBack();
            ApiResponse::exception($e);
        }
    }
}
