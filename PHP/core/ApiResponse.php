<?php
/**
 * ApiResponse — Réponses JSON standardisées
 * 
 * Principe SOLID : Single Responsibility — ne fait que formater les réponses API.
 * Principe DRY : Remplace les dizaines de json_encode() dupliqués dans chaque endpoint.
 */
class ApiResponse
{
    /**
     * Réponse de succès
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
     * Réponse d'erreur
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
     * Réponse d'exception (logging automatique)
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
