<?php /** includes/chatbot.php — attend $p (parametres) déjà chargé. */ ?>
<div id="chatbot" class="fixed bottom-5 right-5 z-[90] flex flex-col items-end pointer-events-none">
  <div id="chatbot-panel" class="mb-4 w-[21rem] max-w-[88vw] bg-white rounded-2xl shadow-2xl overflow-hidden border border-black/5 origin-bottom-right scale-0 opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-ink px-5 py-4 flex items-center gap-3">
      <?php if (!empty($p['logo'])): ?>
        <img src="<?= e(photo_url($p['logo'])) ?>" class="w-10 h-10 rounded-full object-cover ring-2 ring-brand-600" alt="">
      <?php else: ?>
        <span class="w-10 h-10 rounded-full bg-brand-600 grid place-items-center text-white font-display"><?= e(mb_substr($p['nom_restaurant'], 0, 1)) ?></span>
      <?php endif; ?>
      <div class="flex-1">
        <p class="text-white font-bold text-sm leading-tight"><?= e($p['nom_restaurant']) ?></p>
        <p class="text-white/60 text-xs flex items-center gap-1.5">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-success-500"></span>
          </span>
          En ligne sur WhatsApp
        </p>
      </div>
      <button id="chatbot-close" class="text-white/50 hover:text-white p-1" aria-label="Fermer">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-4 space-y-2.5 max-h-80 overflow-y-auto">
      <div class="text-sm text-ink/80 bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 chatbot-bubble">
        Bonjour 👋 Je peux vous aider à commander, réserver une table ou en savoir plus sur nos services. Que souhaitez-vous faire ?
      </div>
      <div class="grid gap-2 pt-1">
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite passer une commande.')) ?>" target="_blank" rel="noopener" class="chatbot-quick-reply">
          <i class="fa-solid fa-utensils"></i> Passer une commande
        </a>
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite réserver une table.')) ?>" target="_blank" rel="noopener" class="chatbot-quick-reply">
          <i class="fa-solid fa-calendar-check"></i> Réserver une table
        </a>
        <a href="<?= e(whatsapp_link('Bonjour, je souhaite en savoir plus sur le service traiteur.')) ?>" target="_blank" rel="noopener" class="chatbot-quick-reply">
          <i class="fa-solid fa-champagne-glasses"></i> Service traiteur
        </a>
        <a href="services.php" class="chatbot-quick-reply">
          <i class="fa-solid fa-concierge-bell"></i> Voir tous les services
        </a>
        <a href="<?= e(whatsapp_link("Bonjour, j'ai une question.")) ?>" target="_blank" rel="noopener" class="chatbot-quick-reply">
          <i class="fa-solid fa-circle-question"></i> Une autre question
        </a>
      </div>
    </div>
    <div class="p-3 border-t border-gray-100">
      <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener"
         class="w-full inline-flex items-center justify-center gap-2 bg-success-600 hover:bg-success-700 text-white font-bold text-sm px-4 py-3 rounded-xl transition">
        <i class="fa-brands fa-whatsapp"></i> Ouvrir WhatsApp
      </a>
    </div>
  </div>

  <button id="chatbot-toggle" class="relative w-16 h-16 rounded-full bg-success-600 hover:bg-success-700 shadow-2xl grid place-items-center text-white transition hover:scale-105 pointer-events-auto" aria-label="Ouvrir le chat WhatsApp">
    <span class="absolute inset-0 rounded-full bg-success-500 animate-ping opacity-40" id="chatbot-pulse"></span>
    <i class="fa-brands fa-whatsapp text-3xl relative"></i>
  </button>
</div>

<button id="back-to-top" class="fixed bottom-5 left-5 z-[80] w-12 h-12 rounded-full bg-ink text-white grid place-items-center shadow-xl opacity-0 pointer-events-none translate-y-4 transition-all duration-300" aria-label="Remonter en haut">
  <i class="fa-solid fa-arrow-up"></i>
</button>