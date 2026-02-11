import type { Metadata } from "next";
import { Outfit, Plus_Jakarta_Sans } from "next/font/google";
import "./globals.css";

const outfit = Outfit({
  variable: "--font-outfit",
  subsets: ["latin"],
});

const plusJakarta = Plus_Jakarta_Sans({
  variable: "--font-plus-jakarta",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "WhatsApp Money | Lucro Passivo com seu WhatsApp",
  description: "Alugue seu WhatsApp para nossa rede de marketing e ganhe dinheiro todos os dias. 100% seguro, automático e sem complicações.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-BR">
      <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
      </head>
      <body
        className={`${outfit.variable} ${plusJakarta.variable} font-sans antialiased`}
      >
        {children}
      </body>
    </html>
  );
}
