'use client';

import { useState } from 'react';

// Team data
const accTeam = [
  { name: 'Nat', lastName: 'BENNET', country: '🇬🇧', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Sergio', lastName: 'HERNÁNDEZ', country: '🇪🇸', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Phil', lastName: 'SOURCY', country: '🇨🇦', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Joakim', lastName: 'ERIKSSON', country: '🇸🇪', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Matteo', lastName: 'MASTROMAURO', country: '🇮🇹', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Gianluca', lastName: 'ZAMBIONE', country: '🇮🇹', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Fabio', lastName: 'FAAR', country: '🇮🇹', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Danny', lastName: 'MEELDIJK', country: '🇳🇱', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Federico', lastName: 'ZAMBLERA', country: '🇮🇹', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'James', lastName: 'FARISH', country: '🇬🇧', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Elmārs', lastName: 'MIĶELSONS', country: '🇱🇻', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Will', lastName: 'FRIEDMANN', country: '🇫🇷', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Florian', lastName: 'OCHSMANN', country: '🇩🇪', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'José', lastName: 'GARCÍA', country: '🇪🇸', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Menno', lastName: 'PETERS', country: '🇳🇱', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
];

const lmuTeam = [
  { name: 'Giuseppe', lastName: 'DINOIA', country: '🇮🇹', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Paul', lastName: 'MÖLLER', country: '🇩🇪', instagram: 'https://instagram.com' },
  { name: 'Jesse', lastName: 'AALBREGT', country: '🇳🇱', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Denis', lastName: 'EBERT', country: '🇩🇪', instagram: 'https://instagram.com' },
  { name: 'Thato', lastName: 'MOTUBATSE', country: '🇿🇦', twitch: 'https://twitch.tv' },
  { name: 'Wilson', lastName: 'GIGÉ', country: '🇫🇷', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Lukas', lastName: 'OESTERREICH', country: '🇩🇪', instagram: 'https://instagram.com', youtube: 'https://youtube.com' },
  { name: 'Luca', lastName: 'GÖNNHEIMER', country: '🇩🇪', instagram: 'https://instagram.com', youtube: 'https://youtube.com' },
  { name: 'Gianluca', lastName: 'WALCZAK', country: '🇩🇪', twitch: 'https://twitch.tv' },
  { name: 'Kyan', lastName: 'HEYNINCK', country: '🇧🇪', instagram: 'https://instagram.com', twitch: 'https://twitch.tv', youtube: 'https://youtube.com' },
  { name: 'Kyle', lastName: 'WILLIAMS', country: '🇿🇦', instagram: 'https://instagram.com' },
  { name: 'Alex', lastName: 'LUCKY', country: '🇮🇹', instagram: 'https://instagram.com' },
  { name: 'Aidan', lastName: 'WINCHESTER', country: '🇬🇧', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
];

const iracingTeam = [
  { name: 'Ethan', lastName: 'AMBURG', country: '🇺🇸', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'Parker', lastName: 'SOUKUP', country: '🇺🇸', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'James', lastName: 'CURTIN', country: '🇺🇸', instagram: 'https://instagram.com', youtube: 'https://youtube.com' },
  { name: 'André', lastName: 'DAMRAT', country: '🇩🇪', instagram: 'https://instagram.com', twitch: 'https://twitch.tv' },
  { name: 'CJ', lastName: 'FARISH', country: '🇺🇸', instagram: 'https://instagram.com' },
];

export default function Home() {
  const [activeSection, setActiveSection] = useState<'acc' | 'lmu' | 'iracing'>('acc');

  return (
    <main className="bg-black text-white min-h-screen">
      {/* Navigation */}
      <nav className="fixed top-0 w-full z-50 bg-black/80 backdrop-blur-md border-b border-purple-900/30">
        <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <img src="/logo.png" alt="XCLusive" className="h-12" />
          </div>
          <div className="flex gap-8 text-sm font-medium">
            <a href="#about" className="hover:text-purple-400 transition">ABOUT</a>
            <a href="#teams" className="hover:text-purple-400 transition">TEAMS</a>
            <a href="#partners" className="hover:text-purple-400 transition">PARTNERS</a>
            <a href="https://discord.gg/AHNTFY9Uuu" target="_blank" className="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded transition">JOIN DISCORD</a>
          </div>
        </div>
      </nav>

      {/* Hero */}
      <section className="relative min-h-screen flex items-center justify-center overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-purple-900/20 via-black to-black"></div>
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-20 left-20 w-96 h-96 bg-purple-600 rounded-full filter blur-3xl"></div>
          <div className="absolute bottom-20 right-20 w-96 h-96 bg-pink-600 rounded-full filter blur-3xl"></div>
        </div>
        
        <div className="relative z-10 text-center px-6 pt-20">
          <div className="mb-8 animate-fade-in">
            <img src="/logo.png" alt="XCLusive Gaming Events" className="h-32 mx-auto mb-8" />
          </div>
          <h1 className="text-6xl md:text-8xl font-black mb-6 bg-gradient-to-r from-purple-400 via-pink-400 to-purple-600 bg-clip-text text-transparent tracking-tight leading-tight">
            THE LION IS BORN<br />TO DOMINATE
          </h1>
          <p className="text-xl md:text-2xl text-gray-300 max-w-3xl mx-auto mb-12 leading-relaxed">
            From console championships to global PC competition.<br />
            <span className="text-purple-400 font-semibold">XCLusive Esports</span> sets the standard in sim racing excellence.
          </p>
          <div className="flex gap-4 justify-center flex-wrap">
            <a href="#teams" className="bg-purple-600 hover:bg-purple-700 px-8 py-4 rounded-lg font-bold text-lg transition transform hover:scale-105">
              VIEW TEAMS
            </a>
            <a href="https://discord.gg/AHNTFY9Uuu" target="_blank" className="border-2 border-purple-600 hover:bg-purple-600/10 px-8 py-4 rounded-lg font-bold text-lg transition">
              JOIN COMMUNITY
            </a>
          </div>
        </div>

        <div className="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
          <svg className="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
      </section>

      {/* About */}
      <section id="about" className="py-24 px-6 bg-gradient-to-b from-black to-purple-950/20">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-5xl md:text-6xl font-black mb-6 bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
              OUR LEGACY
            </h2>
            <div className="w-24 h-1 bg-gradient-to-r from-purple-600 to-pink-600 mx-auto"></div>
          </div>
          
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <p className="text-lg text-gray-300 leading-relaxed mb-6">
                <span className="text-purple-400 font-bold">XCLUSIVE ESPORTS</span> was born from the highly competitive ACC console championships, where it quickly established itself as a dominant force in sim racing.
              </p>
              <p className="text-lg text-gray-300 leading-relaxed mb-6">
                Built on a foundation of <span className="text-white font-semibold">performance, structure, and community</span>, the team has grown into one of the most recognized and competitive console-based esports organizations.
              </p>
              <p className="text-lg text-gray-300 leading-relaxed">
                Now, the team is entering a new phase. Expanding into the PC scene, XCLUSIVE ESPORTS is taking its competitive DNA to the global stage, stepping into top splits and challenging established names in the industry.
              </p>
            </div>
            <div className="bg-gradient-to-br from-purple-900/40 to-pink-900/40 p-8 rounded-2xl border border-purple-700/50">
              <div className="space-y-6">
                <div>
                  <div className="text-5xl font-black text-purple-400 mb-2">7000+</div>
                  <div className="text-gray-400 uppercase tracking-wide">Active Members</div>
                </div>
                <div>
                  <div className="text-5xl font-black text-purple-400 mb-2">33</div>
                  <div className="text-gray-400 uppercase tracking-wide">Professional Drivers</div>
                </div>
                <div>
                  <div className="text-5xl font-black text-purple-400 mb-2">3</div>
                  <div className="text-gray-400 uppercase tracking-wide">Racing Platforms</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Teams Section */}
      <section id="teams" className="py-24 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-5xl md:text-6xl font-black mb-6 bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
              OUR TEAMS
            </h2>
            <div className="w-24 h-1 bg-gradient-to-r from-purple-600 to-pink-600 mx-auto mb-8"></div>
          </div>

          {/* Platform Selector */}
          <div className="flex justify-center gap-4 mb-16 flex-wrap">
            <button
              onClick={() => setActiveSection('acc')}
              className={`px-8 py-4 rounded-lg font-bold text-lg transition ${
                activeSection === 'acc'
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-800 text-gray-400 hover:bg-gray-700'
              }`}
            >
              ACC CONSOLE
            </button>
            <button
              onClick={() => setActiveSection('lmu')}
              className={`px-8 py-4 rounded-lg font-bold text-lg transition ${
                activeSection === 'lmu'
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-800 text-gray-400 hover:bg-gray-700'
              }`}
            >
              LE MANS ULTIMATE
            </button>
            <button
              onClick={() => setActiveSection('iracing')}
              className={`px-8 py-4 rounded-lg font-bold text-lg transition ${
                activeSection === 'iracing'
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-800 text-gray-400 hover:bg-gray-700'
              }`}
            >
              iRACING
            </button>
          </div>

          {/* ACC Team */}
          {activeSection === 'acc' && (
            <div className="animate-fade-in">
              <div className="bg-gradient-to-br from-purple-900/40 to-black p-8 rounded-2xl border border-purple-700/50 mb-12">
                <h3 className="text-3xl font-black text-purple-400 mb-4">XCL x ASSETTO CORSA COMPETIZIONE</h3>
                <p className="text-gray-300 leading-relaxed max-w-4xl">
                  The roots of XCL and XCLusive Esports lie here. This is our home, where we compete, and where we set the standard. 
                  Every console sim racer recognizes our purple cars and knows exactly where to find them: <span className="text-white font-semibold">at the front</span>.
                </p>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                {accTeam.map((driver, idx) => (
                  <div key={idx} className="bg-gradient-to-br from-gray-900 to-purple-950/30 p-6 rounded-xl border border-purple-700/30 hover:border-purple-500/50 transition group">
                    <div className="aspect-square bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg mb-4 flex items-center justify-center">
                      <span className="text-4xl font-black text-white opacity-20">{driver.name[0]}</span>
                    </div>
                    <div className="text-sm text-purple-400 mb-1">{driver.name}</div>
                    <div className="text-xl font-black mb-2">{driver.lastName}</div>
                    <div className="text-2xl mb-3">{driver.country}</div>
                    <div className="flex gap-2">
                      {driver.twitch && (
                        <a href={driver.twitch} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/></svg>
                        </a>
                      )}
                      {driver.instagram && (
                        <a href={driver.instagram} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* LMU Team */}
          {activeSection === 'lmu' && (
            <div className="animate-fade-in">
              <div className="bg-gradient-to-br from-purple-900/40 to-black p-8 rounded-2xl border border-purple-700/50 mb-12">
                <h3 className="text-3xl font-black text-purple-400 mb-4">XCL x LE MANS ULTIMATE</h3>
                <p className="text-gray-300 leading-relaxed max-w-4xl">
                  Our LMU drivers mark the next step for XCLusive Esports. Since entering Le Mans Ultimate in 2026, they have quickly positioned themselves at the front, 
                  consistently competing in top splits and setting benchmark performances.
                </p>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                {lmuTeam.map((driver, idx) => (
                  <div key={idx} className="bg-gradient-to-br from-gray-900 to-purple-950/30 p-6 rounded-xl border border-purple-700/30 hover:border-purple-500/50 transition group">
                    <div className="aspect-square bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg mb-4 flex items-center justify-center">
                      <span className="text-4xl font-black text-white opacity-20">{driver.name[0]}</span>
                    </div>
                    <div className="text-sm text-purple-400 mb-1">{driver.name}</div>
                    <div className="text-xl font-black mb-2">{driver.lastName}</div>
                    <div className="text-2xl mb-3">{driver.country}</div>
                    <div className="flex gap-2">
                      {driver.twitch && (
                        <a href={driver.twitch} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/></svg>
                        </a>
                      )}
                      {driver.instagram && (
                        <a href={driver.instagram} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                      )}
                      {driver.youtube && (
                        <a href={driver.youtube} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* iRacing Team */}
          {activeSection === 'iracing' && (
            <div className="animate-fade-in">
              <div className="bg-gradient-to-br from-purple-900/40 to-black p-8 rounded-2xl border border-purple-700/50 mb-12">
                <h3 className="text-3xl font-black text-purple-400 mb-4">XCL x iRACING</h3>
                <p className="text-gray-300 leading-relaxed max-w-4xl">
                  Our iRacing division represents XCLusive's expansion into North American sim racing. Competing in the world's most popular racing simulation, 
                  our drivers bring the same dedication and performance standards that define XCLusive Esports.
                </p>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                {iracingTeam.map((driver, idx) => (
                  <div key={idx} className="bg-gradient-to-br from-gray-900 to-purple-950/30 p-6 rounded-xl border border-purple-700/30 hover:border-purple-500/50 transition group">
                    <div className="aspect-square bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg mb-4 flex items-center justify-center">
                      <span className="text-4xl font-black text-white opacity-20">{driver.name[0]}</span>
                    </div>
                    <div className="text-sm text-purple-400 mb-1">{driver.name}</div>
                    <div className="text-xl font-black mb-2">{driver.lastName}</div>
                    <div className="text-2xl mb-3">{driver.country}</div>
                    <div className="flex gap-2">
                      {driver.twitch && (
                        <a href={driver.twitch} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/></svg>
                        </a>
                      )}
                      {driver.instagram && (
                        <a href={driver.instagram} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                      )}
                      {driver.youtube && (
                        <a href={driver.youtube} className="text-purple-400 hover:text-purple-300 transition" target="_blank">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </section>

      {/* Partners */}
      <section id="partners" className="py-24 px-6 bg-gradient-to-b from-black to-purple-950/20">
        <div className="max-w-6xl mx-auto text-center">
          <h2 className="text-5xl md:text-6xl font-black mb-6 bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
            POWERED BY PROFESSIONALS
          </h2>
          <div className="w-24 h-1 bg-gradient-to-r from-purple-600 to-pink-600 mx-auto mb-16"></div>
          
          <div className="bg-gradient-to-br from-purple-900/40 to-black p-12 rounded-2xl border border-purple-700/50">
            <p className="text-xl text-gray-300 mb-8">
              Interested in partnering with XCLusive Esports?
            </p>
            <a 
              href="https://www.xboxcommunityleague.com/xclusive-esports" 
              target="_blank"
              className="inline-block bg-purple-600 hover:bg-purple-700 px-8 py-4 rounded-lg font-bold text-lg transition transform hover:scale-105"
            >
              BECOME A PARTNER
            </a>
          </div>
        </div>
      </section>

      {/* Merchandise */}
      <section className="py-24 px-6">
        <div className="max-w-6xl mx-auto text-center">
          <div className="bg-gradient-to-br from-pink-900/40 to-purple-900/40 p-12 rounded-2xl border border-pink-700/50">
            <h2 className="text-5xl font-black mb-6 text-white">
              GET YOUR XCLUSIVE MERCHANDISE
            </h2>
            <p className="text-xl text-gray-300 mb-8">
              Represent the pride. Wear the purple.
            </p>
            <button className="bg-pink-600 hover:bg-pink-700 px-8 py-4 rounded-lg font-bold text-lg transition transform hover:scale-105">
              COMING SOON
            </button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-black border-t border-purple-900/30 py-12 px-6">
        <div className="max-w-6xl mx-auto">
          <div className="grid md:grid-cols-3 gap-12 mb-12">
            <div>
              <img src="/logo.png" alt="XCLusive" className="h-16 mb-4" />
              <p className="text-gray-400">
                Dominating sim racing from console to PC. Join the pride.
              </p>
            </div>
            
            <div>
              <h3 className="text-white font-bold mb-4 text-lg">QUICK LINKS</h3>
              <div className="space-y-2">
                <a href="#about" className="block text-gray-400 hover:text-purple-400 transition">About Us</a>
                <a href="#teams" className="block text-gray-400 hover:text-purple-400 transition">Teams</a>
                <a href="https://www.xboxcommunityleague.com" target="_blank" className="block text-gray-400 hover:text-purple-400 transition">XCL Events</a>
              </div>
            </div>
            
            <div>
              <h3 className="text-white font-bold mb-4 text-lg">CONNECT</h3>
              <div className="flex gap-4 mb-4">
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                </a>
                <a href="https://www.instagram.com/xclusive_esport/" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                </a>
                <a href="https://www.youtube.com/@XCL_TV" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <a href="https://www.twitch.tv/xcl_tv" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z"/></svg>
                </a>
              </div>
              <div className="space-y-2 text-sm">
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank" className="block text-gray-400 hover:text-purple-400 transition">XCLusive Racing Community</a>
                <a href="https://discord.gg/3etdhFdahC" target="_blank" className="block text-gray-400 hover:text-purple-400 transition">XCL Events Discord</a>
              </div>
            </div>
          </div>
          
          <div className="border-t border-purple-900/30 pt-8 text-center text-gray-400 text-sm">
            <p>&copy; 2026 XCLusive Gaming Events. All rights reserved. The lion is born to dominate.</p>
          </div>
        </div>
      </footer>

      <style jsx global>{`
        @import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&display=swap');
        
        * {
          font-family: 'Rajdhani', sans-serif;
        }
        
        @keyframes fade-in {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        .animate-fade-in {
          animation: fade-in 0.6s ease-out;
        }
      `}</style>
    </main>
  );
}
