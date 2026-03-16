<?php

require_once __DIR__ . '/../Models/User.php';

class UserController
{
    private User $model;

    public function __construct(PDO $conn)
    {
        $this->model = new User($conn);
    }

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
