document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-chatbot]');
    if (!root) return;
    const panel = root.querySelector('.chatbot-panel');
    const toggle = root.querySelector('[data-chatbot-toggle]');
    const close = root.querySelector('[data-chatbot-close]');
    const form = root.querySelector('[data-chatbot-form]');
    const input = root.querySelector('[data-chatbot-input]');
    const messages = root.querySelector('[data-chatbot-messages]');
    const suggestions = root.querySelector('[data-chatbot-suggestions]');
    const answers = [
        { terms: ['adopt', 'process', 'apply'], answer: 'Start by finding a pet and reviewing their profile. When you are ready, create an account or sign in, send an adoption application, and our team will review it before arranging a conversation or visit.' },
        { terms: ['document', 'paper', 'proof'], answer: 'You will usually need a photo ID, proof of address, and a short reference or home interview. Exact requirements can vary, so please tell us about your situation when you apply.' },
        { terms: ['long', 'time', 'wait'], answer: 'The timeline depends on the pet and the care needed after approval. We aim to keep you informed at every stage rather than promising a fixed date.' },
        { terms: ['luna', 'vaccinat', 'health'], answer: 'Luna is listed as vaccinated and healthy in her current profile. Please speak with the PAWORA team for the latest veterinary records before making a decision.' },
        { terms: ['visit', 'meet'], answer: 'Yes. A meet-and-greet can be part of the responsible adoption process. Contact our team with the pet name and your preferred time so they can guide the next step.' },
        { terms: ['requirement', 'qualif', 'home'], answer: 'We look for a safe, stable home, a clear plan for daily care, and a genuine willingness to learn. Every household is different, so talk to us openly about your needs.' },
        { terms: ['contact', 'email', 'phone', 'support'], answer: 'You can use the Contact page for a general question, complaint, technical issue or adoption concern. We will give you a reference number after your message is received.' },
        { terms: ['available', 'status', 'waiting'], answer: 'Availability is shown on every profile. If a pet is already adopted or reserved, you can join the waiting list and we will keep your contact details with that pet record.' }
    ];

    const addMessage = (text, role = 'bot') => {
        const item = document.createElement('div');
        item.className = `chat-message ${role}`;
        item.textContent = text;
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    };

    const open = () => {
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        window.setTimeout(() => input?.focus(), 150);
    };

    const shut = () => {
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    };

    const respond = (question) => {
        const normalized = question.toLowerCase();
        const match = answers.find((item) => item.terms.some((term) => normalized.includes(term)));
        addMessage(match?.answer || 'I can help with adoption steps, documents, pet availability, visits and contact options. Try asking how adoption works or what to prepare.');
    };

    const send = (question) => {
        const clean = question.trim();
        if (!clean) return;
        addMessage(clean, 'user');
        const typing = document.createElement('div');
        typing.className = 'chat-message bot typing';
        typing.textContent = 'Pawora is thinking…';
        messages.appendChild(typing);
        messages.scrollTop = messages.scrollHeight;
        window.setTimeout(() => {
            typing.remove();
            respond(clean);
        }, 480);
    };

    toggle.addEventListener('click', () => panel.classList.contains('is-open') ? shut() : open());
    document.querySelectorAll('[data-chatbot-open]').forEach((trigger) => trigger.addEventListener('click', (event) => { event.preventDefault(); open(); }));
    close.addEventListener('click', shut);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        send(input.value);
        input.value = '';
    });
    suggestions.addEventListener('click', (event) => {
        const button = event.target.closest('[data-chat-question]');
        if (button) send(button.dataset.chatQuestion);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) shut();
    });
});
