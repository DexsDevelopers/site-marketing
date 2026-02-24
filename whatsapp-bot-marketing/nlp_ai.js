export class NLPBot {
    constructor() {
        this.memory = new Map(); // Armazena contexto: jid -> { messageCount, lastIntent, lastInteract }

        // Motor de Intenções (Base de Conhecimento "Do Zero")
        this.intents = [
            {
                name: 'saudacao',
                keywords: ['ola', 'oi', 'bom dia', 'boa tarde', 'boa noite', 'opa', 'eae', 'tudo bem', 'blz', 'tudo bom', 'fala'],
                responses: [
                    'Olá! Tudo ótimo por aqui, e com você?',
                    'Opa, tudo bem?',
                    'Bom dia! Como estão as coisas?',
                    'E aí! Qual a boa de hoje?',
                    'Oi, que bom falar com você, tudo certo?'
                ]
            },
            {
                name: 'pergunta_generica',
                keywords: ['como', 'porque', 'onde', 'quem', 'qual', 'pode', 'sabe', 'duvida', '?'],
                responses: [
                    'Olha, eu não tenho certeza absoluta, mas vou verificar.',
                    'Boa pergunta. Preciso dar uma olhada nisso detalhadamente.',
                    'Não sei te dizer de cabeça agora, me dá um tempo pra ver.',
                    'Depende muito do contexto... o que você acha sobre isso?',
                    'Sinceramente, não sei kkk mas vou pesquisar.'
                ]
            },
            {
                name: 'confirmacao',
                keywords: ['sim', 'isso', 'exato', 'claro', 'com certeza', 'vdd', 'verdade', 'concordo', 'aham', 'uhum'],
                responses: [
                    'Pois é, eu também acho.',
                    'Exatamente isso!',
                    'Faz todo sentido.',
                    'Show, estamos na mesma página então.',
                    'Concordo plenamente.'
                ]
            },
            {
                name: 'negacao',
                keywords: ['nao', 'nunca', 'jamais', 'errado', 'discordo', 'nada a ver', 'nem fodendo'],
                responses: [
                    'Entendo seu ponto de vista, mas é complexo.',
                    'Sério? Mas por quê acha isso?',
                    'Ah, não sabia disso não.',
                    'Vish, meio complicado isso né...',
                    'Sério? eu achava que era diferente.'
                ]
            },
            {
                name: 'risada',
                keywords: ['kkk', 'rsrs', 'haha', 'lmao', 'lol', 'rs'],
                responses: [
                    'Kkkkkk ai ai',
                    'Hahaha pois é',
                    'Tô rindo mas é de nervoso rs',
                    'Kkkk muito boa essa',
                    'Rsrs acontece nas melhores famílias.'
                ]
            },
            {
                name: 'despedida',
                keywords: ['tchau', 'flw', 'falou', 'ate logo', 'abraco', 'fui', 'vou indo'],
                responses: [
                    'Valeu, um abraço!',
                    'Até mais!',
                    'Falou, tamo junto.',
                    'Tchau tchau! Qualquer coisa me chama.',
                    'Bom descanso, até a próxima!'
                ]
            }
        ];

        this.defaultResponses = [
            'Entendi perfeitamente.', 'Legal isso.', 'Interessante esse ponto de vista.', 'Pode crer, é verdade.',
            'Ah, sim, saquei.', 'Me conta mais sobre isso depois.', 'Nossa, não tinha pensado por esse lado ainda.',
            'Sério mesmo?', 'Nossa, que loucura foda.', 'Massa demais isso aí.', 'Complicado né, mas a gente vai vivendo.',
            'Faz sentido.', 'Pois é, a vida tem dessas coisas.'
        ];
    }

    // Processo de Normalização Textual (NLP Básico)
    normalize(text) {
        if (!text) return '';
        return text.toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, "") // Remove acentos
            .trim();
    }

    // Análise Semântica Baseada em Score e Processamento
    analyze(text, jid) {
        const normalizedText = this.normalize(text);
        const now = Date.now();

        // Sistema de Memória / Contexto por Número
        if (!this.memory.has(jid)) {
            this.memory.set(jid, { messageCount: 0, lastIntent: null, lastInteract: now });
        }

        const userContext = this.memory.get(jid);
        userContext.messageCount++;
        userContext.lastInteract = now;

        let matchedIntent = null;
        let bestScore = 0;

        // Algoritmo de Classificação (Naive Rule-Based Scoring)
        for (const intent of this.intents) {
            let score = 0;
            for (const keyword of intent.keywords) {
                // Validação de palavra isolada ou pontuação para evitar falso positivo
                const regex = new RegExp(`(^|\\s|[?.,!])${this.normalize(keyword)}($|\\s|[?.,!])`, 'g');
                if (regex.test(normalizedText)) {
                    score += 2; // match exato pesa mais
                } else if (normalizedText.includes(this.normalize(keyword))) {
                    score += 1; // match parcial
                }
            }

            if (score > bestScore) {
                bestScore = score;
                matchedIntent = intent;
            }
        }

        userContext.lastIntent = matchedIntent ? matchedIntent.name : 'desconhecido';
        this.memory.set(jid, userContext);

        // Assembly de Resposta Gerativa (Templates + Randomização)
        if (matchedIntent) {
            const responses = matchedIntent.responses;
            return responses[Math.floor(Math.random() * responses.length)];
        }

        // Comportamento Contextual: Se já trocaram muitas mensagens, a IA muda o tom
        if (userContext.messageCount > 4 && Math.random() > 0.6) {
            return "A gente tá conversando bastante hoje né? Haha. Mas continua, tá bem interessante.";
        }

        // Fallback Generativo Variável
        return this.defaultResponses[Math.floor(Math.random() * this.defaultResponses.length)];
    }

    // Gera o primeiro envio do ciclo de aquecimento com gatilhos naturais
    generateOpener() {
        const openers = [
            "Oie, tudo certinho aí?",
            "E aí, como foi seu dia?",
            "Opa, tá por aí?",
            "Bom dia! Muito ocupado hoje?",
            "Fala! Te chamei só pra ver se o WhatsApp tava funcionando...",
            "Oi! Quando tiver um tempinho me dá um alô.",
            "Testando a internet aqui, a minha tá super lenta, a sua tá normal?",
            "Olá, passando pra desejar uma ótima semana!",
            "Eae sumido, tudo bem?"
        ];
        return openers[Math.floor(Math.random() * openers.length)];
    }
}

export const nlpEngine = new NLPBot();
