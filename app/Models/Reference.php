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

    public function getAllFlat(): array
    {
        $stmt = $this->conn->prepare("
            SELECT nr.id as id, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom
            FROM noms_references nr
            JOIN sous_types st ON nr.id_sous_type = st.id
            JOIN types t ON st.id_type = t.id
            ORDER BY t.nom_type ASC, st.nom_sous_type ASC, nr.nom_reference ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(array $ids): array
    {
        $errors = [];
        $deleted = 0;

        foreach ($ids as $id) {
            // Check if there are objects using this reference
            $checkStmt = $this->conn->prepare("SELECT COUNT(*) FROM objets WHERE id_nom_reference = ?");
            $checkStmt->execute([$id]);
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                // Get the reference name for the error message
                $nameStmt = $this->conn->prepare("SELECT nom_reference FROM noms_references WHERE id = ?");
                $nameStmt->execute([$id]);
                $refName = $nameStmt->fetchColumn();

                $errors[] = "Impossible de supprimer la référence '$refName' : des objets y sont encore associés. Veuillez d'abord supprimer ou modifier les objets correspondants.";
                continue;
            }

            // Get sous_type id to check for later cleanup
            $stStmt = $this->conn->prepare("SELECT id_sous_type FROM noms_references WHERE id = ?");
            $stStmt->execute([$id]);
            $sousTypeId = $stStmt->fetchColumn();

            if ($sousTypeId) {
                // Delete the reference
                $delStmt = $this->conn->prepare("DELETE FROM noms_references WHERE id = ?");
                $delStmt->execute([$id]);
                $deleted++;

                // Cleanup: Check if sous_type is now empty
                $checkSt = $this->conn->prepare("SELECT COUNT(*) FROM noms_references WHERE id_sous_type = ?");
                $checkSt->execute([$sousTypeId]);
                if ($checkSt->fetchColumn() == 0) {
                    $tStmt = $this->conn->prepare("SELECT id_type FROM sous_types WHERE id = ?");
                    $tStmt->execute([$sousTypeId]);
                    $typeId = $tStmt->fetchColumn();

                    $delSt = $this->conn->prepare("DELETE FROM sous_types WHERE id = ?");
                    $delSt->execute([$sousTypeId]);

                    if ($typeId) {
                        // Cleanup: Check if type is now empty
                        $checkT = $this->conn->prepare("SELECT COUNT(*) FROM sous_types WHERE id_type = ?");
                        $checkT->execute([$typeId]);
                        if ($checkT->fetchColumn() == 0) {
                            $delT = $this->conn->prepare("DELETE FROM types WHERE id = ?");
                            $delT->execute([$typeId]);
                        }
                    }
                }
            }
        }

        return ['success' => count($errors) === 0, 'deleted' => $deleted, 'errors' => $errors];
    }
}
