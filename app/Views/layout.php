<?php
/**
 * Layout principal — Vue racine de l'application
 *
 * Assemble tous les partials pour construire la page complète.
 * Chaque section est isolée dans son propre fichier pour la maintenabilité.
 */
$viewDir = __DIR__ . '/partials';
?>
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion du matériel</title>
    <link rel="stylesheet" href="css/output.css" />
  </head>
  <body
    class="font-sans bg-white min-h-screen leading-relaxed text-gray-800"
  >
    <?php include $viewDir . '/sidebar.php'; ?>

    <div id="mainContent" class="main-content p-5">
      <div
        class="max-w-[1400px] mx-auto bg-white rounded-2xl shadow-custom-lg border border-custom-border relative"
      >
        <?php include $viewDir . '/header.php'; ?>

        <div
          class="p-8 bg-white border-b border-custom-border flex flex-wrap gap-5 items-center"
        >
          <div
            id="main_horizontal_wrapper"
            class="flex flex-wrap items-center w-full gap-4"
          >
            <div class="min-w-[200px]">
              <select
                id="modeSelector"
                class="w-full px-4 py-3 border-2 border-custom-brandLight rounded-full text-sm font-bold text-custom-brandLight transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
              >
                <option value="ajout">➕ Ajouter</option>
                <option value="suppression">🗑️ Supprimer</option>
                <option value="modification">✏️ Modifier</option>
                <option value="consultation" selected>🔍 Consulter</option>
              </select>
            </div>

            <div id="actionForms" class="flex-1 pl-3">
              <?php include $viewDir . '/forms/add-item.php'; ?>
              <?php include $viewDir . '/forms/delete-item.php'; ?>
              <?php include $viewDir . '/forms/update-item.php'; ?>
              <?php include $viewDir . '/forms/consultation.php'; ?>
            </div>
          </div>

          <?php include $viewDir . '/modals/box-manager.php'; ?>

          <?php include $viewDir . '/inventory-table.php'; ?>
        </div>

        <!-- Conteneurs legacy cachés -->
        <div id="section_ajout" class="hidden"></div>
        <div id="section_suppression" class="hidden"></div>
        <div id="section_modification" class="hidden"></div>
        <div id="section_consultation" class="hidden"></div>

        <?php include $viewDir . '/modals/barcode-generator.php'; ?>
        <?php include $viewDir . '/modals/reference-manager.php'; ?>
        <?php include $viewDir . '/scripts.php'; ?>
      </div>
    </div>
  </body>
</html>
