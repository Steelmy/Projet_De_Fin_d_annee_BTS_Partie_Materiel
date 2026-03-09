<?php
// Note: bootstrap.php via db_connect.php set le header JSON, 
// mais FPDF l'écrase avec application/pdf dans Output()
require_once 'db_connect.php';
require('fpdf/fpdf.php');

class PDF_MC_Table extends FPDF {
    protected $widths;
    protected $aligns;

    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    function GetRowHeight($data) {
        $nb = 0;
        for($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        return 7 * $nb;
    }

    function CheckTableSpace($totalHeight, $maxPercent = 40) {
        $pageHeight = $this->h;
        $threshold = ($pageHeight * $maxPercent) / 100;
        
        // Si le tableau prend moins ou autant que le maxPercent (ex 40%) autorisé
        // et qu'il dépasse la limite de la page actuelle ("PageBreakTrigger")
        if ($totalHeight <= $threshold) {
             if ($this->GetY() + $totalHeight > $this->PageBreakTrigger) {
                $this->AddPage($this->CurOrientation);
            }
        }
        // Sinon, le tableau est tellement grand qu'on doit laisser le Row() gérer lui-même le multi-page
    }

    function Row($data, $fill = false) {
        // Calculate height of the row
        $h = $this->GetRowHeight($data);
        
        // Issue a page break first if needed
        $this->CheckPageBreak($h);
        
        // Draw the cells of the row
        for($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            
            // Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Draw the border + fill
            if ($fill) {
                $this->Rect($x, $y, $w, $h, 'DF');
            } else {
                $this->Rect($x, $y, $w, $h, 'D');
            }
            
            // Print the text
            $this->MultiCell($w, 7, $data[$i], 0, $a);
            
            // Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        // Go to the next line
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt) {
        // Computes the number of lines a MultiCell of width w will take
        if(!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = $this->CurrentFont['cw'];
        if($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb-1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}

try {
    // Récupérer tous les objets
    $stmt = $conn->prepare("
        SELECT 
            o.Code_bar,
            o.Type,
            o.Nom,
            o.Etat,
            u.Prénom,
            u.Nom AS Nom_utilisateur,
            c.Nom AS Nom_Caisse
        FROM Objet o
        LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
        LEFT JOIN Caisse c ON o.Caisse_id = c.id
        ORDER BY o.Type, o.Nom
    ");
    $stmt->execute();
    $materiels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    $pdf = new PDF_MC_Table();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    
    // Titre
    $pdf->Cell(0, 10, utf8_decode('Inventaire du Matériel'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, utf8_decode('Généré le: ' . date('d/m/Y à H:i')), 0, 1, 'C');
    $pdf->Ln(5);

    $maxPageWidth = 210 * 0.98;

    // --- TABLEAU PRINCIPAL ---
    $pdf->SetFont('Arial', 'B', 9);
    $headers = ['Code-barre', 'Type', utf8_decode('Nom'), utf8_decode('État'), 'Utilisateur', 'Caisse'];
    $cols = count($headers);
    $widths = [];
    for ($i = 0; $i < $cols; $i++) {
        $widths[$i] = $pdf->GetStringWidth($headers[$i]) + 4; 
    }

    $pdf->SetFont('Arial', '', 8);
    $tableData = [];
    foreach ($materiels as $materiel) {
        $utilisateur = '-';
        if ($materiel['Etat'] !== 'disponible' && $materiel['Prénom']) {
            $utilisateur = utf8_decode($materiel['Prénom'] . ' ' . $materiel['Nom_utilisateur']);
        }
        $caisse = !empty($materiel['Nom_Caisse']) ? utf8_decode($materiel['Nom_Caisse']) : '-';
        
        $row = [
            utf8_decode($materiel['Code_bar']),
            utf8_decode($materiel['Type']),
            utf8_decode($materiel['Nom']),
            utf8_decode($materiel['Etat']),
            $utilisateur,
            $caisse
        ];
        $tableData[] = $row;
        
        for ($i = 0; $i < $cols; $i++) {
            $w = $pdf->GetStringWidth($row[$i]) + 4;
            if ($w > $widths[$i]) {
                $widths[$i] = $w;
            }
        }
    }

    $totalWidth = array_sum($widths);
    if ($totalWidth > $maxPageWidth) {
        $scale = $maxPageWidth / $totalWidth;
        for ($i = 0; $i < $cols; $i++) {
            $widths[$i] = floor($widths[$i] * $scale);
        }
        $totalWidth = array_sum($widths);
    }
    
    $startX = (210 - $totalWidth) / 2;
    $pdf->SetLeftMargin($startX);
    $pdf->SetX($startX);

    $pdf->SetWidths($widths);

    // Vérifier si le tableau complet tient ou doit être déplacé
    // (Utile uniquement s'il est très petit, par sécurité)
    $pdf->SetFont('Arial', '', 8);
    $totalHeightMain = 8; // En-tête (h:8)
    foreach ($tableData as $row) {
        $totalHeightMain += $pdf->GetRowHeight($row);
    }
    $pdf->CheckTableSpace($totalHeightMain, 40);

    // En-têtes du tableau
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->SetAligns(['C', 'C', 'C', 'C', 'C', 'C']);
    
    for ($i = 0; $i < $cols; $i++) {
        $pdf->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Données
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetAligns(['L', 'L', 'L', 'C', 'L', 'L']);
    $fill = false;
    foreach ($tableData as $row) {
        if ($fill) {
            $pdf->SetFillColor(240, 240, 240);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        $pdf->Row($row, true);
        $fill = !$fill;
    }

    // Restore margins
    $pdf->SetLeftMargin(10);
    $pdf->SetX(10);

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
            // Récupérer les objets de la caisse d'abord pour calculer la hauteur
            $stmtObjetsCaisse->execute([$caisse['id']]);
            $objets = $stmtObjetsCaisse->fetchAll(PDO::FETCH_ASSOC);

            // Titre de la caisse
            $cTitleHeight = 20; 
            $caisseTitle = 'Caisse : ' . $caisse['Nom'] . ' (' . $caisse['Etat'] . ')';
            if ($caisse['Etat'] !== 'disponible' && !empty($caisse['Prénom'])) {
                $caisseTitle .= ' - Utilisateur : ' . $caisse['Prénom'] . ' ' . $caisse['Nom_utilisateur'];
            }

            if (count($objets) > 0) {
                // Calculate widths
                $pdf->SetFont('Arial', 'B', 9);
                $cHeaders = ['Code-barre', 'Type', utf8_decode('Nom'), utf8_decode('État')];
                $cCols = count($cHeaders);
                $cWidths = [];
                for ($i = 0; $i < $cCols; $i++) {
                    $cWidths[$i] = $pdf->GetStringWidth($cHeaders[$i]) + 4;
                }
                
                $pdf->SetFont('Arial', '', 8);
                $cTableData = [];
                foreach ($objets as $objet) {
                    $row = [
                        utf8_decode($objet['Code_bar']),
                        utf8_decode($objet['Type']),
                        utf8_decode($objet['Nom']),
                        utf8_decode($objet['Etat'])
                    ];
                    $cTableData[] = $row;
                    
                    for ($i = 0; $i < $cCols; $i++) {
                        $w = $pdf->GetStringWidth($row[$i]) + 4;
                        if ($w > $cWidths[$i]) {
                            $cWidths[$i] = $w;
                        }
                    }
                }
                
                $cTotalWidth = array_sum($cWidths);
                if ($cTotalWidth > $maxPageWidth) {
                    $scale = $maxPageWidth / $cTotalWidth;
                    for ($i = 0; $i < $cCols; $i++) {
                        $cWidths[$i] = floor($cWidths[$i] * $scale);
                    }
                    $cTotalWidth = array_sum($cWidths);
                }
                
                $pdf->SetWidths($cWidths);
                
                // Calcul de l'espace requis (Titre + Header + Données)
                $pdf->SetFont('Arial', '', 8);
                $totalHeightCaisse = $cTitleHeight + 8; // Titre(20) + En-têtes(8)
                foreach ($cTableData as $row) {
                    $totalHeightCaisse += $pdf->GetRowHeight($row);
                }
                $pdf->CheckTableSpace($totalHeightCaisse, 40);

                // --- Maintenant on affiche ! ---
                $pdf->SetLeftMargin(10);
                $pdf->SetX(10);
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, utf8_decode($caisseTitle), 0, 1, 'L');

                $cStartX = (210 - $cTotalWidth) / 2;
                $pdf->SetLeftMargin($cStartX);
                $pdf->SetX($cStartX);
                
                // En-têtes
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(255, 235, 200);
                $pdf->SetAligns(['C', 'C', 'C', 'C']);
                
                for ($i = 0; $i < $cCols; $i++) {
                    $pdf->Cell($cWidths[$i], 8, $cHeaders[$i], 1, 0, 'C', true);
                }
                $pdf->Ln();

                // Données
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetAligns(['L', 'L', 'L', 'C']);
                $fillCaisse = false;
                foreach ($cTableData as $row) {
                    if ($fillCaisse) {
                        $pdf->SetFillColor(250, 245, 240);
                    } else {
                        $pdf->SetFillColor(255, 255, 255);
                    }
                    $pdf->Row($row, true);
                    $fillCaisse = !$fillCaisse;
                }
            } else {
                $totalHeightCaisse = $cTitleHeight + 8; // Titre + message vide
                $pdf->CheckTableSpace($totalHeightCaisse, 40);

                $pdf->SetLeftMargin(10);
                $pdf->SetX(10);
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, utf8_decode($caisseTitle), 0, 1, 'L');
                
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->Cell(0, 8, utf8_decode('Aucun objet dans cette caisse.'), 0, 1, 'L');
            }
        }
    }

    $pdf->SetLeftMargin(10);
    $pdf->SetX(10);

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
