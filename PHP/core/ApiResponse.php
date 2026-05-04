<?php

/**
 * Réponses JSON standardisées pour l'API.
 *
 * Toutes les méthodes terminent l'exécution par `exit` après envoi de la réponse,
 * pour éviter qu'un code en aval ne réécrive accidentellement la sortie.
 */
class ApiResponse
{
    /**
     * Envoie une réponse de succès `{success: true, ...$data}`.
     *
     * @param array<string, mixed> $data Charges utiles à fusionner dans la réponse.
     * @param string $message Message optionnel ajouté sous la clé `message`.
     * @return never
     */
    public static function success(array $data = [], string $message = ''): void
    {
        $response = ['success' => true];
        if (!empty($message)) {
            $response['message'] = $message;
        }
        $response = array_merge($response, $data);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envoie une réponse d'erreur métier `{success: false, message}`.
     *
     * @param string $message Message lisible décrivant l'erreur.
     * @param int $httpCode Code HTTP à émettre (par défaut 400).
     * @return never
     */
    public static function error(string $message, int $httpCode = 400): void
    {
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }

        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envoie une réponse 500 pour exception non gérée et journalise via `$GLOBALS['logger']`.
     *
     * @param Throwable $e Exception à signaler.
     * @return never
     */
    public static function exception(Throwable $e): void
    {
        global $logger;
        if ($logger) {
            $logger->error('Exception non gérée', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de base de données: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
