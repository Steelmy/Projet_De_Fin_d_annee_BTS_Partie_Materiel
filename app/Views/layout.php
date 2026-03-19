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
    class="font-sans bg-[#f3f4f6] min-h-screen leading-relaxed text-[#333]"
  >
    <?php include $viewDir . '/sidebar.php'; ?>

    <div id="mainContent" class="main-content min-h-screen transition-all duration-300">
      <div class="p-[20px] max-w-[1400px] mx-auto w-full">
        <?php include $viewDir . '/header.php'; ?>

        <div class="bg-white rounded-[12px] p-[25px] shadow-[0_4px_6px_rgba(0,0,0,0.05)] flex flex-wrap gap-[20px] items-center mb-[20px]">
          <div
            id="main_horizontal_wrapper"
            class="flex flex-wrap items-center w-full gap-[15px]"
          >
            <div class="min-w-[200px]">
              <select
                id="modeSelector"
                class="w-full px-4 py-3 border border-[#ddd] rounded-lg text-[14px] font-bold text-[#333] transition-all duration-300 bg-white focus:outline-none focus:border-[#b8a274]"
              >
                <option value="ajout">Ajouter</option>
                <option value="suppression">Supprimer</option>
                <option value="modification">Modifier</option>
                <option value="consultation" selected>Consulter</option>
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

        </div>

        <?php include $viewDir . '/inventory-table.php'; ?>

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
