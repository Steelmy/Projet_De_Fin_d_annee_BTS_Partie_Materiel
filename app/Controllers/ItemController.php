<?php

require_once __DIR__ . '/../Models/Item.php';

/**
 * Contrôleur des opérations sur les objets (matériel).
 *
 * Couvre la création multiple, la consultation, la mise à jour d'état,
 * la suppression, la restitution et l'introspection (par type/nom, disponibles…).
 */
class ItemController
{
    /** Nombre maximal d'objets créés par un seul ajout (à garder aligné avec `max` du champ `nombre_materiel`). */
    private const MAX_ITEMS_PER_ADD = 30;

    /** @var Item Modèle d'accès aux objets. */
    private Item $model;

    /** @var PDO Connexion PDO active (utilisée pour les transactions). */
    private PDO $conn;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->model = new Item($conn);
        $this->conn = $conn;
    }

    /**
     * Liste une page de matériels avec leurs métadonnées.
     * Entrée GET : `page`, `per_page` (15 par défaut, 100 max), `sort`, `direction` (asc|desc),
     * filtres `code_barre` (partiel), `type`, `sous_type`, `nom` (exacts).
     * Sortie : `data` (lignes de la page), `total` (lignes filtrées), `total_all` (toutes les lignes),
     * `page` (ramenée sur la dernière page existante), `per_page`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function index(): void
    {
        try {
            [$page, $perPage] = Pagination::fromRequest(15, 100);

            $filters = [];
            foreach (['code_barre', 'type', 'sous_type', 'nom'] as $key) {
                $filters[$key] = isset($_GET[$key]) ? trim((string) $_GET[$key]) : '';
            }
            $sort = isset($_GET['sort']) ? (string) $_GET['sort'] : '';
            $direction = ($_GET['direction'] ?? '') === 'desc' ? 'desc' : 'asc';

            $total = $this->model->countFiltered($filters);
            $page = Pagination::clampPage($page, $total, $perPage);
            $hasFilters = array_filter($filters, fn($v) => $v !== '') !== [];

            ApiResponse::success([
                'data' => $this->model->getPage($filters, $sort, $direction, $perPage, ($page - 1) * $perPage),
                'total' => $total,
                'total_all' => $hasFilters ? $this->model->countFiltered([]) : $total,
                'page' => $page,
                'per_page' => $perPage,
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Liste les objets commentés (id, id_com, Code_bar, Type, Nom, com_user, com_admin)
     * pour les pastilles « nouveau commentaire ».
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function commented(): void
    {
        try {
            ApiResponse::success(['data' => $this->model->getCommented()]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Récupère le détail d'un objet à partir de son code-barres.
     * Entrée GET : `code_barre`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function show(): void
    {
        try {
            $codeBarre = isset($_GET['code_barre']) ? trim($_GET['code_barre']) : '';
            if (empty($codeBarre)) {
                ApiResponse::error('Code-barre requis');
            }

            $objet = $this->model->getByBarcode($codeBarre);
            if (!$objet) {
                ApiResponse::error('Objet non trouvé');
            }

            $response = [
                'materiel' => [
                    'id' => $objet['id'],
                    'code_barre' => $objet['Code_bar'],
                    'type_materiel' => $objet['Type'],
                    'sous_type_materiel' => $objet['Sous_type'],
                    'nom_materiel' => $objet['Nom'],
                    'etat' => $objet['Etat'],
                    'caisse_id' => $objet['Caisse_id'],
                    'caisse_nom' => $objet['caisse_nom'],
                    'created_at' => $objet['created_at'],
                    'updated_at' => $objet['updated_at']
                ]
            ];

            if ($objet['Etat'] !== 'disponible' && $objet['user_nom']) {
                $response['materiel']['utilisateur'] = [
                    'id' => $objet['Emprunteur_id'],
                    'nom_complet' => $objet['user_prenom'] . ' ' . $objet['user_nom']
                ];
            }

            ApiResponse::success($response);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Crée plusieurs objets identiques en série.
     * Entrée POST : `type_materiel`, `sous_type_materiel`, `nom_materiel`, `nombre`.
     *
     * @return void Réponse JSON via ApiResponse contenant les IDs et codes-barres générés.
     */
    public function store(): void
    {
        try {
            $type = isset($_POST['type_materiel']) ? trim($_POST['type_materiel']) : '';
            $sousType = isset($_POST['sous_type_materiel']) ? trim($_POST['sous_type_materiel']) : '';
            $nom = isset($_POST['nom_materiel']) ? trim($_POST['nom_materiel']) : '';
            $nombre = isset($_POST['nombre']) ? intval($_POST['nombre']) : 0;

            if (empty($type) || empty($nom) || $nombre <= 0) {
                ApiResponse::error('Tous les champs sont requis et le nombre doit être supérieur à 0');
            }

            if ($nombre > self::MAX_ITEMS_PER_ADD) {
                ApiResponse::error('Impossible d\'ajouter plus de ' . self::MAX_ITEMS_PER_ADD . ' matériels à la fois');
            }

            // Tout ou rien : si une insertion échoue, aucun objet (ni référence créée en cascade) n'est conservé.
            $this->conn->beginTransaction();
            try {
                $resultats = [];
                for ($i = 0; $i < $nombre; $i++) {
                    $resultats[] = $this->model->create($type, $sousType, $nom);
                }
                $this->conn->commit();
            } catch (PDOException $e) {
                $this->conn->rollBack();
                throw $e;
            }

            $idsAjoutes = array_column($resultats, 'id');
            $codesGeneres = array_column($resultats, 'code_bar');

            ApiResponse::success([
                'ids_ajoutes' => $idsAjoutes,
                'codes_barres_generes' => $codesGeneres
            ], "$nombre matériel(s) ajouté(s) avec succès");
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Met à jour l'état (et l'éventuel emprunteur) d'un objet.
     * Refuse l'opération si l'objet est dans une caisse.
     * Entrée POST : `code_barre`, `etat`, `reserveur_emprunteur` (requis hors `disponible`).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function update(): void
    {
        try {
            $codeBarre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';
            $etat = isset($_POST['etat']) ? trim($_POST['etat']) : '';
            $emprunteurId = isset($_POST['reserveur_emprunteur']) ? intval($_POST['reserveur_emprunteur']) : 1;

            if (empty($codeBarre) || empty($etat)) {
                ApiResponse::error('Le code-barre et l\'état sont requis');
            }

            $objet = $this->model->findByBarcode($codeBarre);
            if (!$objet) {
                ApiResponse::error('Objet non trouvé');
            }

            if (!empty($objet['Caisse_id'])) {
                ApiResponse::error('Cet objet est actuellement dans une caisse. Veuillez d\'abord le retirer de la caisse avant de pouvoir le modifier.');
            }

            $emprunteurIdFinal = null;
            if ($etat === 'disponible') {
                $emprunteurIdFinal = null;
            } else {
                if ($emprunteurId < 1) {
                    ApiResponse::error('Veuillez sélectionner un utilisateur pour un objet réservé ou emprunté');
                }
                $emprunteurIdFinal = $emprunteurId;
            }

            $this->model->updateState($codeBarre, $etat, $emprunteurIdFinal);


            ApiResponse::success([
                'updated' => [
                    'code_barre' => $codeBarre,
                    'type' => $objet['Type'],
                    'nom' => $objet['Nom'],
                    'etat' => $etat
                ]
            ], 'Objet modifié avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Supprime un objet, sous réserve qu'il soit `disponible` et hors caisse.
     * Entrée POST : `code_barre`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function destroy(): void
    {
        try {
            $codeBarre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';
            if (empty($codeBarre)) {
                ApiResponse::error('Le code-barre du matériel est requis');
            }

            $objet = $this->model->findByBarcode($codeBarre);
            if (!$objet) {
                ApiResponse::error('Objet non trouvé');
            }

            if (!empty($objet['Caisse_id'])) {
                ApiResponse::error('Cet objet est actuellement dans une caisse. Veuillez d\'abord le retirer de la caisse avant de pouvoir le supprimer.');
            }

            if ($objet['Etat'] !== 'disponible') {
                ApiResponse::error('Cet objet est actuellement ' . $objet['Etat'] . '. Veuillez d\'abord le remettre en état "disponible" avant de pouvoir le supprimer.');
            }

            $this->model->delete($codeBarre);


            ApiResponse::success([
                'deleted' => [
                    'code_barre' => $objet['Code_bar'],
                    'type' => $objet['Type'],
                    'nom' => $objet['Nom']
                ]
            ], 'Objet supprimé avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Liste les objets actuellement disponibles (et hors caisse).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function available(): void
    {
        try {
            $objets = $this->model->getAvailable();
            ApiResponse::success([
                'objets' => $objets,
                'total' => count($objets)
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Liste les objets correspondant à un couple (type, nom de référence).
     * Entrée GET : `type`, `nom`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function ids(): void
    {
        try {
            $type = isset($_GET['type']) ? trim($_GET['type']) : '';
            $nom = isset($_GET['nom']) ? trim($_GET['nom']) : '';

            if (empty($type) || empty($nom)) {
                ApiResponse::error('Le type et le nom sont requis');
            }

            $results = $this->model->getByTypeAndName($type, $nom);
            ApiResponse::success([
                'ids' => $results
            ], count($results) . ' matériel(s) trouvé(s)');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

    /**
     * Restitue un objet emprunté/réservé : remet à disponible et clôture son historique.
     * Refuse si l'objet est en caisse ou déjà disponible.
     * Entrée POST : `code_barre`.
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function restitute(): void
    {
        try {
            $codeBarre = isset($_POST['code_barre']) ? trim($_POST['code_barre']) : '';

            if (empty($codeBarre)) {
                ApiResponse::error('Le code-barre est requis pour la restitution');
            }

            $objet = $this->model->findByBarcode($codeBarre);
            if (!$objet) {
                ApiResponse::error('Objet non trouvé');
            }

            if (!empty($objet['Caisse_id'])) {
                ApiResponse::error('Cet objet est actuellement dans une caisse. Veuillez d\'abord le retirer de la caisse.');
            }

            if ($objet['Etat'] === 'disponible') {
                ApiResponse::error('Cet objet est déjà disponible.');
            }

            $this->model->restitute($codeBarre);


            ApiResponse::success([
                'restituted' => [
                    'code_barre' => $codeBarre
                ]
            ], 'Objet restitué avec succès');
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }
}
