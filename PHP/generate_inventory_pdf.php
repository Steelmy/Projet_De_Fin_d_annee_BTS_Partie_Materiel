<?php
// Note: bootstrap.php via db_connect.php set le header JSON, 
// mais FPDF l'écrase avec application/pdf dans Output()
require_once 'db_connect.php';
require('fpdf/fpdf.php');

try {

    // Récupérer tous les objets
    $stmt = $conn->prepare("
        SELECT 
            o.Code_bar,
            o.Type,
            o.Nom,
            o.Etat,
            u.Prénom,
            u.Nom AS Nom_utilisateur
        FROM Objet o
        LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
        ORDER BY o.Type, o.Nom
    ");
    $stmt->execute();
    $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    
    // Titre
    $pdf->Cell(0, 10, utf8_decode('Inventaire du Matériel'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, utf8_decode('Généré le: ' . date('d/m/Y à H:i')), 0, 1, 'C');
    $pdf->Ln(5);

    // En-têtes du tableau
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(35, 8, 'Code-barre', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Type', 1, 0, 'C', true);
    $pdf->Cell(45, 8, utf8_decode('Nom'), 1, 0, 'C', true);
    $pdf->Cell(25, 8, utf8_decode('État'), 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Utilisateur', 1, 1, 'C', true);

    // Données
    $pdf->SetFont('Arial', '', 8);
    $fill = false;
    foreach ($materiels as $materiel) {
        // Couleur alternée
        if ($fill) {
            $pdf->SetFillColor(240, 240, 240);
        }
        
        $utilisateur = '-';
        if ($materiel['Etat'] !== 'disponible' && $materiel['Prénom']) {
            $utilisateur = utf8_decode($materiel['Prénom'] . ' ' . $materiel['Nom_utilisateur']);
        }
        
        $pdf->Cell(35, 7, utf8_decode($materiel['Code_bar']), 1, 0, 'L', $fill);
        $pdf->Cell(40, 7, utf8_decode($materiel['Type']), 1, 0, 'L', $fill);
        $pdf->Cell(45, 7, utf8_decode($materiel['Nom']), 1, 0, 'L', $fill);
        $pdf->Cell(25, 7, utf8_decode($materiel['Etat']), 1, 0, 'C', $fill);
        $pdf->Cell(45, 7, $utilisateur, 1, 1, 'L', $fill);
        
        $fill = !$fill;
    }

    // --- TABLEAUX DE CAISSE ---
    $stmtCaisses = $conn->prepare("
        SELECT 
            c.id,
            c.Nom,
            c.Etat,
            u.Prénom,
            u.Nom AS Nom_utilisateur
        FROM Caisse c
        LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
        ORDER BY c.Nom
    ");
    $stmtCaisses->execute();
    $caisses = $stmtCaisses->fetchAll(PDO::FETCH_ASSOC);

    if (count($caisses) > 0) {
        $stmtObjetsCaisse = $conn->prepare("
            SELECT Code_bar, Type, Nom, Etat
            FROM Objet
            WHERE Caisse_id = ?
            ORDER BY Type, Nom
        ");

        foreach ($caisses as $caisse) {
            $pdf->Ln(10);
            
            // Titre de la caisse
            $pdf->SetFont('Arial', 'B', 12);
            $caisseTitle = 'Caisse : ' . $caisse['Nom'] . ' (' . $caisse['Etat'] . ')';
            if ($caisse['Etat'] !== 'disponible' && !empty($caisse['Prénom'])) {
                $caisseTitle .= ' - Utilisateur : ' . $caisse['Prénom'] . ' ' . $caisse['Nom_utilisateur'];
            }
            $pdf->Cell(0, 10, utf8_decode($caisseTitle), 0, 1, 'L');

            // Récupérer les objets de la caisse
            $stmtObjetsCaisse->execute([$caisse['id']]);
            $objets = $stmtObjetsCaisse->fetchAll(PDO::FETCH_ASSOC);

            if (count($objets) > 0) {
                // En-têtes du tableau de la caisse
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(255, 235, 200); // Couleur distincte
                $pdf->Cell(45, 8, 'Code-barre', 1, 0, 'C', true);
                $pdf->Cell(50, 8, 'Type', 1, 0, 'C', true);
                $pdf->Cell(65, 8, utf8_decode('Nom'), 1, 0, 'C', true);
                $pdf->Cell(30, 8, utf8_decode('État'), 1, 1, 'C', true);

                $pdf->SetFont('Arial', '', 8);
                $fillCaisse = false;
                foreach ($objets as $objet) {
                    if ($fillCaisse) {
                        $pdf->SetFillColor(250, 245, 240);
                    }
                    $pdf->Cell(45, 7, utf8_decode($objet['Code_bar']), 1, 0, 'L', $fillCaisse);
                    $pdf->Cell(50, 7, utf8_decode($objet['Type']), 1, 0, 'L', $fillCaisse);
                    $pdf->Cell(65, 7, utf8_decode($objet['Nom']), 1, 0, 'L', $fillCaisse);
                    $pdf->Cell(30, 7, utf8_decode($objet['Etat']), 1, 1, 'C', $fillCaisse);
                    $fillCaisse = !$fillCaisse;
                }
            } else {
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->Cell(0, 8, utf8_decode('Aucun objet dans cette caisse.'), 0, 1, 'L');
            }
        }
    }

    // Pied de page
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, utf8_decode('Total: ' . count($materiels) . ' matériel(s)'), 0, 1, 'R');

    // Sortie du PDF
    $pdf->Output('D', 'Inventaire_Materiel_' . date('Y-m-d') . '.pdf');

} catch(PDOException $e) {
    die('Erreur: ' . $e->getMessage());
}

$conn = null;
?>
