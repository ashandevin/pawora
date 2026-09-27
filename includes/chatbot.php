<?php
declare(strict_types=1);
?>
<div class="chatbot-shell" data-chatbot>
    <button class="chatbot-launcher" type="button" data-chatbot-toggle aria-expanded="false" aria-controls="paworaChat" aria-label="Open Pawora Assistant">
        <i class="bi bi-chat-heart-fill" aria-hidden="true"></i>
        <span class="chatbot-pulse" aria-hidden="true"></span>
    </button>
    <section class="chatbot-panel" id="paworaChat" aria-label="Pawora Assistant chat" aria-hidden="true">
        <div class="chatbot-head">
            <div class="d-flex align-items-center gap-2">
                <span class="chatbot-avatar" aria-hidden="true"><i class="bi bi-heart-fill"></i></span>
                <div><strong>Pawora Assistant</strong><small><span class="online-dot"></span>Here when you need us</small></div>
            </div>
            <button class="chatbot-close" type="button" data-chatbot-close aria-label="Close Pawora Assistant"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <div class="chatbot-messages" data-chatbot-messages aria-live="polite">
            <div class="chat-message bot">Hi, I’m the Pawora Assistant. I can help you understand adoption, documents and next steps. What would you like to know?</div>
        </div>
        <div class="chatbot-suggestions" data-chatbot-suggestions>
            <button type="button" data-chat-question="How do I adopt a pet?">How do I adopt?</button>
            <button type="button" data-chat-question="What documents do I need?">Documents</button>
            <button type="button" data-chat-question="Is Luna vaccinated?">Luna’s health</button>
        </div>
        <form class="chatbot-form" data-chatbot-form>
            <label class="visually-hidden" for="chatbotInput">Type your question</label>
            <input id="chatbotInput" type="text" autocomplete="off" placeholder="Ask a question…" maxlength="240" data-chatbot-input>
            <button type="submit" aria-label="Send question"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
        </form>
        <small class="chatbot-disclaimer">Prototype assistant · For urgent care, contact a local veterinary professional.</small>
    </section>
</div>
