<?php

class PdfController
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function inventory(): void
    {

        try {
            $materiels = $this->fetchMaterials();
            // $caisses = $this->fetchCaisses();

            $pdf = new PDF_MC_Table();
            $pdf->AddPage();
            $maxPageWidth = 210 * 0.98;

            // Titre
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, utf8_decode('Inventaire du Matériel'), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, utf8_decode('Généré le: ' . date('d/m/Y à H:i')), 0, 1, 'C');
            $pdf->Ln(5);

            // Tableau principal
            $this->renderMainTable($pdf, $materiels, $maxPageWidth);

            // Tableaux par caisse
            // $this->renderCaisseTables($pdf, $caisses, $maxPageWidth);

            // Pied de page
            $pdf->SetLeftMargin(10);
            $pdf->SetX(10);
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(0, 5, utf8_decode('Total: ' . count($materiels) . ' matériel(s)'), 0, 1, 'R');

            $pdf->Output('D', 'Inventaire_Materiel_' . date('Y-m-d') . '.pdf');
        } catch (PDOException $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    private function fetchMaterials(): array
    {
        $stmt = $this->conn->prepare("
            SELECT o.Code_bar, o.Type, o.Sous_type, o.Nom, o.Etat,
                   u.Prénom, u.Nom AS Nom_utilisateur, c.Nom AS Nom_Caisse
            FROM objets o
            LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
            LEFT JOIN caisses c ON o.Caisse_id = c.id
            ORDER BY o.Type, o.Nom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchCaisses(): array
    {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.Nom, c.Etat, u.Prénom, u.Nom AS Nom_utilisateur
            FROM caisses c
            LEFT JOIN utilisateurs u ON c.Emprunteur_id = u.id
            ORDER BY c.Nom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function renderMainTable(PDF_MC_Table $pdf, array $materiels, float $maxPageWidth): void
    {
        $pdf->SetFont('Arial', 'B', 9);
        // $headers = ['Code-barre', 'Type', 'Sous-type', utf8_decode('Nom'), utf8_decode('État'), 'Utilisateur', 'Caisse'];
        $headers = ['Code-barre', 'Type', 'Sous-type', utf8_decode('Nom'), utf8_decode('État'), 'Utilisateur'];
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
            // $caisse = !empty($materiel['Nom_Caisse']) ? utf8_decode($materiel['Nom_Caisse']) : '-';

            $row = [
                utf8_decode($materiel['Code_bar']),
                utf8_decode($materiel['Type']),
                utf8_decode($materiel['Sous_type']),
                utf8_decode($materiel['Nom']),
                utf8_decode($materiel['Etat']),
                $utilisateur
                // $caisse
            ];
            $tableData[] = $row;

            for ($i = 0; $i < $cols; $i++) {
                $w = $pdf->GetStringWidth($row[$i]) + 4;
                if ($w > $widths[$i]) {
                    $widths[$i] = $w;
                }
            }
        }

        $this->scaleWidths($widths, $maxPageWidth);
        $totalWidth = array_sum($widths);
        $startX = (210 - $totalWidth) / 2;

        $pdf->SetLeftMargin($startX);
        $pdf->SetX($startX);
        $pdf->SetWidths($widths);

        // Vérifier espace
        $pdf->SetFont('Arial', '', 8);
        $totalHeight = 8;
        foreach ($tableData as $row) {
            $totalHeight += $pdf->GetRowHeight($row);
        }
        $pdf->CheckTableSpace($totalHeight, 40);

        // En-têtes
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 220, 255);
        // $pdf->SetAligns(['C', 'C', 'C', 'C', 'C', 'C', 'C']);
        $pdf->SetAligns(['C', 'C', 'C', 'C', 'C', 'C']);
        for ($i = 0; $i < $cols; $i++) {
            $pdf->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Donnees
        $pdf->SetFont('Arial', '', 8);
        // $pdf->SetAligns(['L', 'L', 'L', 'L', 'C', 'L', 'L']);
        $pdf->SetAligns(['L', 'L', 'L', 'L', 'C', 'L']);
        $fill = false;
        foreach ($tableData as $row) {
            $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
            $pdf->Row($row, true);
            $fill = !$fill;
        }

        $pdf->SetLeftMargin(10);
        $pdf->SetX(10);
    }

    private function renderCaisseTables(PDF_MC_Table $pdf, array $caisses, float $maxPageWidth): void
    {
        if (empty($caisses)) {
            return;
        }

        $stmtObjets = $this->conn->prepare("
            SELECT Code_bar, Type, Sous_type, Nom, Etat
            FROM objets WHERE Caisse_id = ? ORDER BY Type, Nom
        ");

        foreach ($caisses as $caisse) {
            $stmtObjets->execute([$caisse['id']]);
            $objets = $stmtObjets->fetchAll(PDO::FETCH_ASSOC);

            $caisseTitle = 'Caisse : ' . $caisse['Nom'] . ' (' . $caisse['Etat'] . ')';
            if ($caisse['Etat'] !== 'disponible' && !empty($caisse['Prénom'])) {
                $caisseTitle .= ' - Utilisateur : ' . $caisse['Prénom'] . ' ' . $caisse['Nom_utilisateur'];
            }

            if (empty($objets)) {
                $pdf->CheckTableSpace(28, 40);
                $pdf->SetLeftMargin(10);
                $pdf->SetX(10);
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, utf8_decode($caisseTitle), 0, 1, 'L');
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->Cell(0, 8, utf8_decode('Aucun objet dans cette caisse.'), 0, 1, 'L');
                continue;
            }

            $pdf->SetFont('Arial', 'B', 9);
            $cHeaders = ['Code-barre', 'Type', 'Sous-type', utf8_decode('Nom'), utf8_decode('État')];
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
                    utf8_decode($objet['Sous_type']),
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

            $this->scaleWidths($cWidths, $maxPageWidth);
            $cTotalWidth = array_sum($cWidths);

            $pdf->SetWidths($cWidths);

            // Calcul espace
            $pdf->SetFont('Arial', '', 8);
            $totalHeight = 28;
            foreach ($cTableData as $row) {
                $totalHeight += $pdf->GetRowHeight($row);
            }
            $pdf->CheckTableSpace($totalHeight, 40);

            // Titre
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
            $pdf->SetAligns(['C', 'C', 'C', 'C', 'C']);
            for ($i = 0; $i < $cCols; $i++) {
                $pdf->Cell($cWidths[$i], 8, $cHeaders[$i], 1, 0, 'C', true);
            }
            $pdf->Ln();

            // Donnees
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetAligns(['L', 'L', 'L', 'L', 'C']);
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
        }
    }

    private function scaleWidths(array &$widths, float $maxWidth): void
    {
        $totalWidth = array_sum($widths);
        if ($totalWidth > $maxWidth) {
            $scale = $maxWidth / $totalWidth;
            for ($i = 0; $i < count($widths); $i++) {
                $widths[$i] = floor($widths[$i] * $scale);
            }
        }
    }
}

/**
 * Extension FPDF pour tableaux multi-cellules
 */
require_once dirname(__DIR__, 2) . '/php/fpdf/fpdf.php';
class PDF_MC_Table extends FPDF
{
    protected $widths;
    protected $aligns;

    public function SetWidths($w) { $this->widths = $w; }
    public function SetAligns($a) { $this->aligns = $a; }

    public function GetRowHeight($data): float
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        return 7 * $nb;
    }

    public function CheckTableSpace($totalHeight, $maxPercent = 40): void
    {
        $threshold = ($this->h * $maxPercent) / 100;
        if ($totalHeight <= $threshold) {
            if ($this->GetY() + $totalHeight > $this->PageBreakTrigger) {
                $this->AddPage($this->CurOrientation);
            }
        }
    }

    public function Row($data, $fill = false): void
    {
        $h = $this->GetRowHeight($data);
        $this->CheckPageBreak($h);

        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();

            $this->Rect($x, $y, $w, $h, $fill ? 'DF' : 'D');
            $this->MultiCell($w, 7, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    public function CheckPageBreak($h): void
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    public function NbLines($w, $txt): int
    {
        if (!isset($this->CurrentFont)) {
            $this->Error('No font has been set');
        }
        $cw = $this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string) $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}
