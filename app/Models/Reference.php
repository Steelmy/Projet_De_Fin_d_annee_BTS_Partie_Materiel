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
            SELECT t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom
            FROM types t
            LEFT JOIN sous_types st ON st.id_type = t.id
            LEFT JOIN noms_references nr ON nr.id_sous_type = st.id
            ORDER BY t.nom_type ASC, st.nom_sous_type ASC, nr.nom_reference ASC
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
        // 1. Chercher ou créer Type
        $stmtType = $this->conn->prepare("SELECT id FROM types WHERE nom_type = ?");
        $stmtType->execute([$type]);
        $typeId = $stmtType->fetchColumn();
        if (!$typeId) {
            $this->conn->prepare("INSERT INTO types (nom_type) VALUES (?)")->execute([$type]);
            $typeId = $this->conn->lastInsertId();
        }

        // 2. Chercher ou créer Sous_type
        if (empty($sousType)) $sousType = 'Non défini';
        $stmtSousType = $this->conn->prepare("SELECT id FROM sous_types WHERE nom_sous_type = ? AND id_type = ?");
        $stmtSousType->execute([$sousType, $typeId]);
        $sousTypeId = $stmtSousType->fetchColumn();
        if (!$sousTypeId) {
            $this->conn->prepare("INSERT INTO sous_types (nom_sous_type, id_type) VALUES (?, ?)")->execute([$sousType, $typeId]);
            $sousTypeId = $this->conn->lastInsertId();
        }

        // 3. Chercher ou créer Nom_reference
        $stmtNom = $this->conn->prepare("SELECT id FROM noms_references WHERE nom_reference = ? AND id_sous_type = ?");
        $stmtNom->execute([$nom, $sousTypeId]);
        $nomRefId = $stmtNom->fetchColumn();
        if (!$nomRefId) {
            $this->conn->prepare("INSERT INTO noms_references (nom_reference, id_sous_type) VALUES (?, ?)")->execute([$nom, $sousTypeId]);
            return true;
        }

        return false;
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
                $table = 'types';
                $fields = 'DISTINCT nom_type AS Type';
                $orderBy = 'nom_type';
                if (!empty($query)) {
                    $conditions[] = "nom_type LIKE :q_start";
                    $params[':q_start'] = $query . '%';
                }
                break;

            case 'materiel_sous_type':
                $table = 'sous_types st JOIN types t ON st.id_type = t.id';
                $fields = 'DISTINCT st.nom_sous_type AS Sous_type';
                $orderBy = 'st.nom_sous_type';
                $conditions[] = "st.nom_sous_type != ''";
                if (!empty($query)) {
                    $conditions[] = "st.nom_sous_type LIKE :q_start";
                    $params[':q_start'] = $query . '%';
                }
                if (!empty($filter)) {
                    $conditions[] = "t.nom_type = :filter";
                    $params[':filter'] = $filter;
                }
                break;

            case 'materiel_nom':
                $table = 'noms_references nr JOIN sous_types st ON nr.id_sous_type = st.id JOIN types t ON st.id_type = t.id';
                $fields = 'DISTINCT nr.nom_reference AS Nom';
                $orderBy = 'nr.nom_reference';
                if (!empty($query)) {
                    $conditions[] = "nr.nom_reference LIKE :q_start";
                    $params[':q_start'] = $query . '%';
                }
                if (!empty($filter)) {
                    $conditions[] = "t.nom_type = :filter";
                    $params[':filter'] = $filter;
                }
                if (!empty($filterSousType)) {
                    $conditions[] = "st.nom_sous_type = :f_sous_type";
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
