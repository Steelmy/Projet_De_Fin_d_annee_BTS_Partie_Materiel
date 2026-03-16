<?php

require_once __DIR__ . '/../Models/Box.php';
require_once __DIR__ . '/../Models/Item.php';

class BoxController
{
    private Box $boxModel;
    private Item $itemModel;
    private Logger $logger;

    public function __construct(PDO $conn, Logger $logger)
    {
        $this->boxModel = new Box($conn);
        $this->itemModel = new Item($conn);
        $this->logger = $logger;
    }

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

            $this->logger->info("Caisse ajoutée", ['nom' => $nom, 'id' => $caisseId, 'objets' => count($objets)]);
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

            // Préparer les champs à mettre à jour
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

            // Mise à jour du contenu
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

            $this->logger->info("Caisse modifiée", ['id' => $caisse['id'], 'nom' => $nouveauNom ?: $caisse['Nom']]);
            ApiResponse::success([
                'updated' => ['id' => $caisse['id'], 'nom' => $nouveauNom ?: $caisse['Nom']]
            ], 'Caisse modifiée avec succès');
        } catch (PDOException $e) {
            $this->boxModel->rollBack();
            ApiResponse::exception($e);
        }
    }

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

            $this->logger->info("Caisse supprimée", ['id' => $caisse['id'], 'nom' => $caisse['Nom']]);
            ApiResponse::success([
                'deleted' => ['id' => $caisse['id'], 'nom' => $caisse['Nom']]
            ], 'Caisse supprimée avec succès');
        } catch (PDOException $e) {
            $this->boxModel->rollBack();
            ApiResponse::exception($e);
        }
    }
}
