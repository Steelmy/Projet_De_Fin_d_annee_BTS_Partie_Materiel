<?php

require_once __DIR__ . '/../Models/User.php';

/**
 * Contrôleur des opérations sur les utilisateurs.
 */
class UserController
{
    /** @var User Modèle d'accès aux utilisateurs. */
    private User $model;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->model = new User($conn);
    }

    /**
     * Liste tous les utilisateurs au format normalisé pour l'API
     * (clés en minuscules + nom complet précalculé).
     *
     * @return void Réponse JSON via ApiResponse.
     */
    public function index(): void
    {
        try {
            $rows = $this->model->getAll();

            $utilisateurs = [];
            foreach ($rows as $row) {
                $utilisateurs[] = [
                    'id' => $row['id'],
                    'nom' => $row['Nom'],
                    'prenom' => $row['Prénom'],
                    'full_name' => $row['Prénom'] . ' ' . $row['Nom']
                ];
            }

            ApiResponse::success(['utilisateurs' => $utilisateurs]);
        } catch (PDOException $e) {
            ApiResponse::exception($e);
        }
    }
}
