<?php

/**
 * Contrôleur de génération de PDF d'inventaire (export FPDF).
 *
 * Note : ce contrôleur ne renvoie pas de JSON, il déclenche un téléchargement PDF.
 * Les chaînes affichées sont passées via `utf8_decode` car FPDF travaille en latin-1.
 */
class PdfController
{
    /** @var PDO Connexion PDO active. */
    private PDO $conn;

    /**
     * @param PDO $conn Connexion PDO active.
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Génère le PDF d'inventaire et envoie sa réponse en téléchargement.
     *
     * @return void Sortie binaire FPDF (force le download).
     */
    public function inventory(): void
    {
        try {
            $materiels = $this->fetchMaterials();

            $pdf = new PDF_MC_Table();
            $pdf->AddPage();
            $maxPageWidth = 210 * 0.98;

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, utf8_decode('Inventaire du Matériel'), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, utf8_decode('Généré le: ' . date('d/m/Y à H:i')), 0, 1, 'C');
            $pdf->Ln(5);

            $this->renderMainTable($pdf, $materiels, $maxPageWidth);

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

    /**
     * Récupère tous les matériels avec leurs métadonnées (type, utilisateur, caisse) pour l'export.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchMaterials(): array
    {
        $stmt = $this->conn->prepare("
            SELECT o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom, o.Etat,
                   u.Prénom, u.Nom AS Nom_utilisateur, c.Nom AS Nom_Caisse
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            LEFT JOIN utilisateurs u ON o.Emprunteur_id = u.id
            LEFT JOIN caisses c ON o.Caisse_id = c.id
            ORDER BY t.nom_type, nr.nom_reference
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les caisses avec leur utilisateur emprunteur (pour l'export par caisse).
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * Dessine le tableau principal (tous matériels) sur le PDF.
     *
     * @param PDF_MC_Table $pdf Instance FPDF en cours.
     * @param array<int, array<string, mixed>> $materiels Lignes à afficher.
     * @param float $maxPageWidth Largeur disponible (en mm).
     * @return void
     */
    private function renderMainTable(PDF_MC_Table $pdf, array $materiels, float $maxPageWidth): void
    {
        $pdf->SetFont('Arial', 'B', 9);
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

            $row = [
                utf8_decode($materiel['Code_bar']),
                utf8_decode($materiel['Type']),
                utf8_decode($materiel['Sous_type']),
                utf8_decode($materiel['Nom']),
                utf8_decode($materiel['Etat']),
                $utilisateur
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

        $pdf->SetFont('Arial', '', 8);
        $totalHeight = 8;
        foreach ($tableData as $row) {
            $totalHeight += $pdf->GetRowHeight($row);
        }
        $pdf->CheckTableSpace($totalHeight, 40);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->SetAligns(['C', 'C', 'C', 'C', 'C', 'C']);
        for ($i = 0; $i < $cols; $i++) {
            $pdf->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
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

    /**
     * Dessine un tableau récapitulatif par caisse (en-tête + contenu).
     *
     * @param PDF_MC_Table $pdf Instance FPDF en cours.
     * @param array<int, array<string, mixed>> $caisses Lignes de caisses à parcourir.
     * @param float $maxPageWidth Largeur disponible (en mm).
     * @return void
     */
    private function renderCaisseTables(PDF_MC_Table $pdf, array $caisses, float $maxPageWidth): void
    {
        if (empty($caisses)) {
            return;
        }

        $stmtObjets = $this->conn->prepare("
            SELECT o.Code_bar, t.nom_type AS Type, st.nom_sous_type AS Sous_type, nr.nom_reference AS Nom, o.Etat
            FROM objets o
            LEFT JOIN noms_references nr ON o.id_nom_reference = nr.id
            LEFT JOIN sous_types st ON nr.id_sous_type = st.id
            LEFT JOIN types t ON st.id_type = t.id
            WHERE o.Caisse_id = ? ORDER BY t.nom_type, nr.nom_reference
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

            $pdf->SetFont('Arial', '', 8);
            $totalHeight = 28;
            foreach ($cTableData as $row) {
                $totalHeight += $pdf->GetRowHeight($row);
            }
            $pdf->CheckTableSpace($totalHeight, 40);

            $pdf->SetLeftMargin(10);
            $pdf->SetX(10);
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, utf8_decode($caisseTitle), 0, 1, 'L');

            $cStartX = (210 - $cTotalWidth) / 2;
            $pdf->SetLeftMargin($cStartX);
            $pdf->SetX($cStartX);

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(255, 235, 200);
            $pdf->SetAligns(['C', 'C', 'C', 'C', 'C']);
            for ($i = 0; $i < $cCols; $i++) {
                $pdf->Cell($cWidths[$i], 8, $cHeaders[$i], 1, 0, 'C', true);
            }
            $pdf->Ln();

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

    /**
     * Réduit proportionnellement les largeurs de colonnes si leur somme dépasse `maxWidth`.
     *
     * @param array<int, float> $widths Largeurs de colonnes (modifiées par référence).
     * @param float $maxWidth Largeur maximale autorisée.
     * @return void
     */
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

require_once dirname(__DIR__, 2) . '/php/fpdf/fpdf.php';

/**
 * Extension de FPDF pour dessiner des tableaux multi-cellules
 * avec largeurs et alignements configurables, et gestion de saut de page.
 */
class PDF_MC_Table extends FPDF
{
    /** @var array<int, float> Largeurs de colonnes courantes. */
    protected $widths;

    /** @var array<int, string> Alignements par colonne (`L`, `C`, `R`). */
    protected $aligns;

    /**
     * Définit les largeurs de colonnes utilisées par `Row`.
     *
     * @param array<int, float> $w Largeurs (en mm).
     * @return void
     */
    public function SetWidths($w) { $this->widths = $w; }

    /**
     * Définit les alignements de colonnes utilisés par `Row`.
     *
     * @param array<int, string> $a Alignements (`L|C|R`) indexés par colonne.
     * @return void
     */
    public function SetAligns($a) { $this->aligns = $a; }

    /**
     * Calcule la hauteur nécessaire pour afficher une ligne (en tenant
     * compte du wrap MultiCell).
     *
     * @param array<int, string> $data Cellules de la ligne.
     * @return float Hauteur en mm.
     */
    public function GetRowHeight($data): float
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        return 7 * $nb;
    }

    /**
     * Force un saut de page si le tableau ne tient pas sur l'espace restant
     * et si sa hauteur reste sous un certain pourcentage de la page.
     *
     * @param float $totalHeight Hauteur totale prévue (en mm).
     * @param int $maxPercent Pourcentage max de la hauteur de page pour autoriser le saut anticipé.
     * @return void
     */
    public function CheckTableSpace($totalHeight, $maxPercent = 40): void
    {
        $threshold = ($this->h * $maxPercent) / 100;
        if ($totalHeight <= $threshold) {
            if ($this->GetY() + $totalHeight > $this->PageBreakTrigger) {
                $this->AddPage($this->CurOrientation);
            }
        }
    }

    /**
     * Dessine une ligne de tableau avec wrap automatique des cellules.
     *
     * @param array<int, string> $data Cellules de la ligne.
     * @param bool $fill Active le remplissage de fond.
     * @return void
     */
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

    /**
     * Saute une page si la hauteur fournie dépasse l'espace restant.
     *
     * @param float $h Hauteur à insérer (en mm).
     * @return void
     */
    public function CheckPageBreak($h): void
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    /**
     * Calcule le nombre de lignes nécessaires pour afficher `$txt` dans une cellule
     * de largeur `$w` (méthode standard issue des exemples FPDF).
     *
     * @param float $w Largeur de la cellule (en mm). 0 = jusqu'à la marge droite.
     * @param string $txt Texte à mesurer.
     * @return int Nombre de lignes.
     */
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
