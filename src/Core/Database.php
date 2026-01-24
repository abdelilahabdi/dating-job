<?php
namespace Src\Core;

use Dotenv\Dotenv;
use PDO;
use PDOException;


$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

class Database
{
    private static ?Database $instance = null;
    private PDO $db;

    /**
     * Constructeur privé pour empêcher l'instanciation directe de la classe.
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME']
        );

        try {
            $this->db = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            error_log(
                'Database Connection Error: ' .
                $e->getMessage() .
                ' - ' . $e->getFile() . ':' . $e->getLine()
            );

            die('Erreur de connexion à la base de données.');
        }
    }

    /*
     * Méthode statique pour obtenir l'instance unique de la classe.
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    /*
     * Méthode clone privée pour bloquer le clonage de l'instance du classe.
     */
    private function __clone(): void
    {
    }

    /*
     * Méthode getConnection pour obtenir la connexion PDO.
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }
}
