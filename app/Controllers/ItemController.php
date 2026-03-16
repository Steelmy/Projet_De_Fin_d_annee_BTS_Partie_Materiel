<?php

require_once __DIR__ . '/../Models/Item.php';

class ItemController
{
    private Item $model;
    private Logger $logger;

    public function __construct(PDO $conn, Logger $logger)
    {
        $this->model = new Item($conn);
        $this->logger = $logger;
    }

    public function index(): void
    {
        try {
            $materiels = $this->model->getAll();
            ApiResponse::success([
                'data' => $materiels,
                'total' => count($materiels)
            ]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

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

    public function store(): void
    {
        try {
            $type = isset($_POST['type_materiel']) ? trim($_POST['type_materiel']) : '';
            $sousType = isset($_POST['sous_type_materiel']) ? trim($_POST['sous_type_materiel']) : '';
            $nom = isset($_POST['nom_materiel']) ? trim($_POST['nom_materiel']) : '';
            $nombre = isset($_POST['nombre']) ? intval($_POST['nombre']) : 0;
            $codesBarres = isset($_POST['codes_barres']) ? json_decode($_POST['codes_barres'], true) : [];

            if (empty($type) || empty($nom) || $nombre <= 0) {
                ApiResponse::error('Tous les champs sont requis et le nombre doit être supérieur à 0');
            }

            $idsAjoutes = [];

            for ($i = 0; $i < $nombre; $i++) {
                if (!isset($codesBarres[$i]) || empty(trim($codesBarres[$i]))) {
                    ApiResponse::error("Le code-barre est obligatoire pour tous les matériels (manquant pour l'article #" . ($i + 1) . ")");
                }

                $codeBarre = trim($codesBarres[$i]);

                if ($this->model->barcodeExists($codeBarre)) {
                    ApiResponse::error("Le code-barre '$codeBarre' existe déjà. Veuillez en scanner un autre.");
                }

                $idsAjoutes[] = $this->model->create($type, $sousType, $nom, $codeBarre);
            }

            $this->logger->info("Matériel ajouté", ['type' => $type, 'sous_type' => $sousType, 'nom' => $nom, 'nombre' => $nombre]);
            ApiResponse::success([
                'ids_ajoutes' => $idsAjoutes
            ], "$nombre matériel(s) ajouté(s) avec succès");
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }

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

            $this->logger->info("Matériel modifié", ['code_barre' => $codeBarre, 'etat' => $etat]);
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

            $this->logger->info("Matériel supprimé", ['code_barre' => $codeBarre, 'type' => $objet['Type'], 'nom' => $objet['Nom']]);
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
}
