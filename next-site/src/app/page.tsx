import Link from 'next/link';

export default function Home() {
  return (
    <div className="min-h-screen font-sans bg-[#050505] text-white">
      <div className="mesh-bg" />

      <div className="max-w-[1200px] mx-auto px-6">
        {/* --- Navbar --- */}
        <nav className="py-6 flex justify-between items-center sticky top-0 z-50 backdrop-blur-md">
          <div className="font-['Outfit'] text-2xl font-extrabold flex items-center gap-2.5">
            <i className="fab fa-whatsapp text-[#10b981]"></i>
            WA <span className="text-[#10b981]">MONEY</span>
          </div>
          <div className="hidden md:flex gap-6">
            <Link href="/entrar" className="px-7 py-3 rounded-xl font-bold bg-white/5 border border-white/10 hover:bg-white/10 hover:border-white/20 transition-all">
              Acessar Conta
            </Link>
            <Link href="/registrar" className="px-7 py-3 rounded-xl font-bold bg-[#10b981] text-black shadow-[0_4px_20px_rgba(16,185,129,0.3)] hover:-translate-y-0.5 hover:shadow-[0_8px_30px_rgba(16,185,129,0.4)] transition-all">
              Começar Agora
            </Link>
          </div>
        </nav>

        {/* --- Hero --- */}
        <section className="py-24 text-center">
          <div className="inline-block px-4 py-2 rounded-full bg-[#10b981]/10 text-[#10b981] text-sm font-bold border border-[#10b981]/20 mb-10 transition-all opacity-0 animate-[slideUp_1s_ease_0s_forwards]">
            A MAIOR REDE DE MONITORAMENTO DO BRASIL
          </div>
          <h1 className="font-['Outfit'] text-5xl md:text-7xl font-extrabold leading-[1.1] mb-8 tracking-tighter opacity-0 animate-[slideUp_1s_ease_0.1s_forwards]">
            Seu WhatsApp agora gera <span className="bg-gradient-to-r from-[#10b981] to-[#3b82f6] bg-clip-text text-transparent">Lucro Real</span>.
          </h1>
          <p className="text-[#94a3b8] text-xl md:text-2xl max-w-[700px] mx-auto mb-14 opacity-0 animate-[slideUp_1s_ease_0.2s_forwards]">
            Nós usamos sua conexão ociosa para validar campanhas de marketing globais.
            Em troca, você recebe R$ 20,00 por dia diretamente no seu PIX.
          </p>
          <div className="opacity-0 animate-[slideUp_1s_ease_0.3s_forwards]">
            <Link href="/registrar" className="px-12 py-5 rounded-xl font-bold text-xl bg-[#10b981] text-black shadow-[0_4px_20px_rgba(16,185,129,0.3)] hover:-translate-y-0.5 hover:shadow-[0_8px_30px_rgba(16,185,129,0.4)] transition-all inline-flex items-center gap-2">
              <i className="fas fa-play"></i> ATIVAR MINHA CONTA GRÁTIS
            </Link>
            <div className="mt-6 flex justify-center gap-6 text-[#94a3b8] text-sm">
              <span className="flex items-center gap-2 text-white/50"><i className="fas fa-check-circle text-[#10b981]"></i> Sem mensalidades</span>
              <span className="flex items-center gap-2 text-white/50"><i className="fas fa-check-circle text-[#10b981]"></i> Saque diário</span>
              <span className="flex items-center gap-2 text-white/50"><i className="fas fa-check-circle text-[#10b981]"></i> 100% Seguro</span>
            </div>
          </div>
        </section>

        {/* --- Features --- */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
          <FeatureCard
            icon="fa-bolt"
            title="Ativação em 1 Minuto"
            desc="Basta vincular seu número no painel via código e o sistema começa a trabalhar imediatamente."
            delay="0.4s"
          />
          <FeatureCard
            icon="fa-piggy-bank"
            title="Renda Passiva Real"
            desc="Não precisa vender nada, nem convidar ninguém. O lucro é gerado apenas por estar online."
            delay="0.5s"
          />
          <FeatureCard
            icon="fa-shield-alt"
            title="Tecnologia Anti-Ban"
            desc="Nossos algoritmos simulam o comportamento humano para garantir que seu chip fique 100% protegido."
            delay="0.6s"
          />
        </div>

        {/* --- Privacy Banner --- */}
        <section className="bg-gradient-to-br from-[#10b981]/5 to-[#3b82f6]/5 border border-white/10 rounded-[32px] p-8 md:p-16 mb-24 opacity-0 animate-[slideUp_1s_ease_0.7s_forwards]">
          <h2 className="font-['Outfit'] text-4xl mb-6">O que fazemos (e o que NÃO fazemos)</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <PrivacyItem icon="fa-check" title="Validamos Links" text="Usamos sua JID para verificar se links de marketing estão ativos." />
            <PrivacyItem icon="fa-times" title="Zero Acesso a Chat" text="Nunca lemos suas mensagens privadas ou fotos." color="text-red-500" />
            <PrivacyItem icon="fa-times" title="Contatos Intocáveis" text="Jamais enviaremos mensagens para sua família ou amigos." color="text-red-500" />
            <PrivacyItem icon="fa-check" title="Uso Silencioso" text="O bot trabalha em segundo plano sem atrapalhar seu uso do app." />
          </div>
        </section>

        {/* --- Feedbacks --- */}
        <section className="mb-24">
          <h2 className="font-['Outfit'] text-5xl text-center mb-14 tracking-tighter">O que dizem nossos <span className="text-[#10b981]">Parceiros</span></h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <FeedbackCard
              initials="RS"
              name="Ricardo Santos"
              time="há 2 horas"
              text='"No começo achei que era golpe, mas recebi meus primeiros R$ 20,00 no PIX hoje cedo. O sistema é muito simples de usar e o suporte é nota 10!"'
            />
            <FeedbackCard
              initials="AM"
              name="Ana Maria"
              time="há 5 horas"
              text='"O melhor de tudo é que não atrapalha em nada o uso do WhatsApp. Fica lá quietinho em segundo plano e o saldo vai subindo todo dia."'
            />
            <FeedbackCard
              initials="LT"
              name="Lucas Teixeira"
              time="há 8 horas"
              text='"Já testei vários apps de ganhar dinheiro assistindo vídeo, mas esse é o único que realmente paga automático sem precisar fazer nada."'
            />
          </div>
        </section>

        {/* --- CTA Box --- */}
        <section className="bg-gradient-to-r from-[#10b981] to-[#059669] rounded-[32px] py-20 px-8 text-center text-black mb-24 opacity-0 animate-[slideUp_1s_ease_0.8s_forwards]">
          <h2 className="font-['Outfit'] text-5xl font-extrabold mb-6 tracking-tighter">Pronto para começar?</h2>
          <p className="text-xl font-medium mb-12 opacity-90">Junte-se a mais de 12.000 usuários que já estão lucrando com suas conexões.</p>
          <Link href="/registrar" className="bg-black text-white px-16 py-6 rounded-xl font-extrabold text-xl transition-all hover:scale-105 active:scale-95">
            CRIAR MINHA CONTA AGORA
          </Link>
        </section>
      </div>

      <footer className="py-16 border-t border-white/10 text-center text-[#94a3b8]">
        <p>&copy; 2026 WhatsApp Money Technology. Todos os direitos reservados.</p>
      </footer>
    </div>
  );
}

function FeatureCard({ icon, title, desc, delay }: { icon: string, title: string, desc: string, delay: string }) {
  return (
    <div
      className="bg-[#0f0f12] p-8 md:p-12 rounded-[24px] border border-white/10 text-left transition-all hover:border-[#10b981]/30 hover:bg-white/[0.02] hover:-translate-y-1 opacity-0 animate-[slideUp_1s_ease_forwards]"
      style={{ animationDelay: delay }}
    >
      <div className="w-16 h-16 bg-[#10b981]/10 rounded-2xl flex items-center justify-center text-2xl text-[#10b981] mb-8">
        <i className={`fas ${icon}`}></i>
      </div>
      <h3 className="text-xl font-bold mb-4">{title}</h3>
      <p className="text-[#94a3b8]">{desc}</p>
    </div>
  );
}

function PrivacyItem({ icon, title, text, color = "text-[#10b981]" }: { icon: string, title: string, text: string, color?: string }) {
  return (
    <div className="flex gap-4 items-start text-[#94a3b8]">
      <i className={`fas ${icon} mt-1.5 ${color}`}></i>
      <div>
        <b className="text-white">{title}</b>
        <p className="text-sm mt-1">{text}</p>
      </div>
    </div>
  );
}

function FeedbackCard({ initials, name, time, text }: { initials: string, name: string, time: string, text: string }) {
  return (
    <div className="bg-[#0f0f12] p-10 rounded-[24px] border border-white/10 text-left transition-all hover:border-[#10b981] hover:scale-[1.02]">
      <div className="flex gap-1 text-[#fbbf24] mb-6 text-sm">
        {[...Array(5)].map((_, i) => <i key={i} className="fas fa-star"></i>)}
      </div>
      <p className="text-[#94a3b8] italic leading-relaxed mb-8">{text}</p>
      <div className="flex items-center gap-4">
        <div className="w-12 h-12 rounded-full bg-gradient-to-br from-[#10b981] to-[#3b82f6] flex items-center justify-center font-extrabold text-black">
          {initials}
        </div>
        <div>
          <h4 className="font-bold text-white">{name}</h4>
          <span className="text-xs text-[#94a3b8]">{time}</span>
        </div>
      </div>
    </div>
  );
}
