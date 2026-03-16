<?php

class Reference
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getTree(): array
    {
        $stmt = $this->conn->prepare("
            SELECT Type, Sous_type, Nom
            FROM catalogue_references
            ORDER BY Type ASC, Sous_type ASC, Nom ASC
        ");
        $stmt->execute();
        $references = $stmt->fetchAll();

        $tree = [];
        foreach ($references as $ref) {
            $type = $ref['Type'];
            $sousType = $ref['Sous_type'] ?: '';
            $nom = $ref['Nom'];

            if (!isset($tree[$type])) {
                $tree[$type] = [];
            }
            if (!isset($tree[$type][$sousType])) {
                $tree[$type][$sousType] = [];
            }
            $tree[$type][$sousType][] = $nom;
        }

        return $tree;
    }

    public function create(string $type, string $sousType, string $nom): bool
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO catalogue_references (Type, Sous_type, Nom)
            VALUES (:type, :sous_type, :nom)
        ");
        $stmt->execute([
            ':type' => $type,
            ':sous_type' => $sousType,
            ':nom' => $nom
        ]);
        return $stmt->rowCount() > 0;
    }

    public function search(string $type, string $query, string $filter = '', string $filterSousType = ''): array
    {
        $params = [];
        $conditions = [];
        $fields = '';
        $table = '';
        $orderBy = '';

        switch ($type) {
            case 'materiel_type':
                $table = 'catalogue_references';
                $fields = 'DISTINCT Type';
                $orderBy = 'Type';
                if (!empty($query)) {
                    $conditions[] = "Type LIKE :q_start";
                    $params[':q_start'] = $query . '%';
                }
                break;

            case 'materiel_sous_type':
                $table = 'catalogue_references';
                $fields = 'DISTINCT Sous_type';
                $orderBy = 'Sous_type';
                $conditions[] = "Sous_type != ''";
                if (!empty($query)) {
                    $conditions[] = "Sous_type LIKE :q_start";
                    $params[':q_start'] = $query . '%';
                }
                if (!empty($filter)) {
                    $conditions[] = "Type = :filter";
                    $params[':filter'] = $filter;
                }
                break;

            case 'materiel_nom':
                $table = 'catalogue_references';
                $fields = 'DISTINCT Nom';
                $orderBy = 'Nom';
                if (!empty($query)) {
                    $conditions[] = "Nom LIKE :q_start";
                    $params[':q_start'] = $query . '%';
                }
                if (!empty($filter)) {
                    $conditions[] = "Type = :filter";
                    $params[':filter'] = $filter;
                }
                if (!empty($filterSousType)) {
                    $conditions[] = "Sous_type = :f_sous_type";
                    $params[':f_sous_type'] = $filterSousType;
                }
                break;

            default:
                return [];
        }

        $sql = "SELECT $fields FROM $table";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY $orderBy LIMIT 10";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
