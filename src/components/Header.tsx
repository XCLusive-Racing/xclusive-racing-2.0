'use client';

import { useState } from 'react';
import Link from 'next/link';

export default function Header({ onSignUpClick }: { onSignUpClick: () => void }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  return (
    <header className="fixed top-0 w-full z-50 bg-white shadow-lg">
      {/* Topo background */}
      <div className="absolute inset-0 opacity-5 pointer-events-none" style={{
        backgroundImage: 'url(/topo.png)',
        backgroundSize: 'cover',
      }}></div>

      <nav className="relative max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        {/* Logo */}
        <Link href="/" className="flex items-center gap-2 z-10">
          <img src="/logo.png" alt="XCLusive" className="h-10" />
        </Link>

        {/* Desktop Navigation */}
        <div className="hidden md:flex items-center gap-8 text-sm font-medium text-gray-900 uppercase tracking-wide">
          <Link href="/#about" className="hover:text-purple-600 transition">About</Link>
          <Link href="/#teams" className="hover:text-purple-600 transition">Teams</Link>
          <Link href="/race" className="hover:text-purple-600 transition">Race</Link>
          <Link href="/#partners" className="hover:text-purple-600 transition">Partners</Link>
          <a href="https://raven.gg/stores/xclusive-esports/" target="_blank" className="hover:text-purple-600 transition">Merchandise</a>
        </div>

        {/* Right side buttons */}
        <div className="flex items-center gap-4 z-10">
          <a 
            href="https://discord.gg/AHNTFY9Uuu" 
            target="_blank"
            className="hidden md:block bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded font-bold text-sm transition"
          >
            JOIN DISCORD
          </a>
          <button
            onClick={onSignUpClick}
            className="flex items-center gap-2 text-purple-600 hover:text-purple-700 font-bold transition"
          >
            <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            PROFILE
          </button>
        </div>

        {/* Mobile menu button */}
        <button 
          onClick={() => setIsMenuOpen(!isMenuOpen)}
          className="md:hidden z-10"
        >
          <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </nav>

      {/* Mobile menu */}
      {isMenuOpen && (
        <div className="md:hidden bg-white border-t border-gray-200 px-6 py-4 space-y-4">
          <Link href="/#about" className="block text-sm font-medium text-gray-900 uppercase hover:text-purple-600">About</Link>
          <Link href="/#teams" className="block text-sm font-medium text-gray-900 uppercase hover:text-purple-600">Teams</Link>
          <Link href="/race" className="block text-sm font-medium text-gray-900 uppercase hover:text-purple-600">Race</Link>
          <Link href="/#partners" className="block text-sm font-medium text-gray-900 uppercase hover:text-purple-600">Partners</Link>
          <a href="https://raven.gg/stores/xclusive-esports/" target="_blank" className="block text-sm font-medium text-gray-900 uppercase hover:text-purple-600">Merchandise</a>
          <button onClick={onSignUpClick} className="block w-full text-left text-sm font-medium text-purple-600 uppercase">Sign Up / Profile</button>
          <a href="https://discord.gg/AHNTFY9Uuu" target="_blank" className="block text-sm font-medium text-purple-600 uppercase">Join Discord</a>
        </div>
      )}
    </header>
  );
}
