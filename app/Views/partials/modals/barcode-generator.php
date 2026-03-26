<div
  id="barcode-modal"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 backdrop-blur-sm modal-backdrop"
  ></div>
  <div
    class="bg-white p-5 border border-[#888] w-[80%] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl text-center"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer transition-colors duration-200 hover:text-black focus:text-black"
      id="close-barcode-modal"
      >&times;</span
    >
    <h2 class="text-2xl font-bold mb-4">Générateur</h2>
    <div class="my-4">
      <label for="barcode-qty" class="font-medium"
        >Nombre de codes :
        <span id="qty-val" class="font-bold text-custom-primary">1</span></label
      >
      <input
        type="range"
        id="barcode-qty"
        min="1"
        max="52"
        value="1"
        class="w-full mt-2.5 accent-custom-brandLight"
      />
    </div>
    <button
      id="btn-print"
      class="px-6 py-3 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-full font-semibold text-sm shadow-input hover:brightness-90 active:brightness-75 transition-all duration-300"
    >
      Imprimer
    </button>
    <div
      id="print-zone"
      class="flex flex-wrap justify-center gap-4 mt-6 max-h-[400px] overflow-y-auto custom-scrollbar border-t border-gray-100 pt-4"
    ></div>
  </div>
</div>
