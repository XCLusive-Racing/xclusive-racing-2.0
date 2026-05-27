'use client';

import { useState } from 'react';
import Header from '@/components/Header';
import SignUpModal from '@/components/SignUpModal';

export default function Home() {
  const [signUpOpen, setSignUpOpen] = useState(false);
  const [activeSection, setActiveSection] = useState<'acc' | 'lmu' | 'iracing'>('acc');

  const accTeam = [
    { name: 'Nat', lastName: 'BENNET', country: '🇬🇧' },
    { name: 'Sergio', lastName: 'HERNÁNDEZ', country: '🇪🇸' },
    { name: 'Phil', lastName: 'SOURCY', country: '🇨🇦' },
    { name: 'Joakim', lastName: 'ERIKSSON', country: '🇸🇪' },
    { name: 'Matteo', lastName: 'MASTROMAURO', country: '🇮🇹' },
    { name: 'Gianluca', lastName: 'ZAMBIONE', country: '🇮🇹' },
  ];

  const lmuTeam = [
    { name: 'Giuseppe', lastName: 'DINOIA', country: '🇮🇹' },
    { name: 'Paul', lastName: 'MÖLLER', country: '🇩🇪' },
    { name: 'Jesse', lastName: 'AALBREGT', country: '🇳🇱' },
    { name: 'Denis', lastName: 'EBERT', country: '🇩🇪' },
  ];

  const iracingTeam = [
    { name: 'Ethan', lastName: 'AMBURG', country: '🇺🇸' },
    { name: 'Parker', lastName: 'SOUKUP', country: '🇺🇸' },
    { name: 'James', lastName: 'CURTIN', country: '🇺🇸' },
  ];

  return (
    <main className="bg-white min-h-screen">
      <Header onSignUpClick={() => setSignUpOpen(true)} />

      {/* Hero Section */}
      <section className="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
        <div 
          className="absolute inset-0 opacity-10"
          style={{
            backgroundImage: 'url(/topo.png)',
            backgroundSize: 'cover',
          }}
        ></div>

        <div className="relative z-10 text-center px-6">
          <div className="mb-12 animate-fade-in">
            <img src="/logo.png" alt="XCLusive" className="h-32 mx-auto mb-8" />
          </div>
          <h1 className="text-6xl md:text-7xl font-black mb-6 text-gray-900 uppercase italic leading-tight tracking-tight">
            THE LION IS BORN<br />TO DOMINATE
          </h1>
          <p className="text-xl md:text-2xl text-gray-700 max-w-3xl mx-auto mb-12 leading-relaxed">
            From console championships to global PC competition.<br />
            <span className="text-purple-600 font-black">XCLUSIVE ESPORTS</span> sets the standard in sim racing excellence.
          </p>
          <div className="flex gap-4 justify-center flex-wrap">
            <button 
              onClick={() => setSignUpOpen(true)}
              className="bg-purple-600 hover:bg-purple-700 text-white px-8 py-4 rounded font-black uppercase text-lg transition transform hover:scale-105"
            >
              SIGN UP NOW
            </button>
            <a href="/#teams" className="border-2 border-purple-600 text-purple-600 hover:bg-purple-50 px-8 py-4 rounded font-black uppercase text-lg transition">
              VIEW TEAMS
            </a>
          </div>
        </div>

        <div className="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
          <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
      </section>

      {/* About Section */}
      <section id="about" className="py-24 px-6 bg-gradient-to-b from-white to-gray-50">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-5xl md:text-6xl font-black mb-6 text-gray-900 uppercase italic">
              OUR LEGACY
            </h2>
            <div className="w-24 h-1 bg-gradient-to-r from-purple-600 to-pink-600 mx-auto"></div>
          </div>
          
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <p className="text-lg text-gray-700 leading-relaxed mb-6">
                <span className="text-purple-600 font-black">XCLUSIVE ESPORTS</span> was born from the highly competitive ACC console championships, where it quickly established itself as a dominant force in sim racing.
              </p>
              <p className="text-lg text-gray-700 leading-relaxed mb-6">
                Built on a foundation of <span className="text-black font-black">performance, structure, and community</span>, the team has grown into one of the most recognized and competitive console-based esports organizations.
              </p>
              <p className="text-lg text-gray-700 leading-relaxed">
                Now, the team is entering a new phase. Expanding into the PC scene, XCLUSIVE ESPORTS is taking its competitive DNA to the global stage, stepping into top splits and challenging established names in the industry.
              </p>
            </div>
            <div className="bg-gradient-to-br from-purple-100 to-pink-100 p-8 rounded-2xl border-2 border-purple-300">
              <div className="space-y-6">
                <div>
                  <div className="text-5xl font-black text-purple-600 mb-2">7000+</div>
                  <div className="text-gray-700 uppercase font-bold tracking-wide">Active Members</div>
                </div>
                <div>
                  <div className="text-5xl font-black text-purple-600 mb-2">33</div>
                  <div className="text-gray-700 uppercase font-bold tracking-wide">Professional Drivers</div>
                </div>
                <div>
                  <div className="text-5xl font-black text-purple-600 mb-2">3</div>
                  <div className="text-gray-700 uppercase font-bold tracking-wide">Racing Platforms</div>
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
            <h2 className="text-5xl md:text-6xl font-black mb-6 text-gray-900 uppercase italic">
              OUR TEAMS
            </h2>
            <div className="w-24 h-1 bg-gradient-to-r from-purple-600 to-pink-600 mx-auto"></div>
          </div>

          {/* Platform Selector */}
          <div className="flex justify-center gap-4 mb-16 flex-wrap">
            <button
              onClick={() => setActiveSection('acc')}
              className={`px-8 py-4 rounded font-black uppercase text-lg transition ${
                activeSection === 'acc'
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              ACC CONSOLE
            </button>
            <button
              onClick={() => setActiveSection('lmu')}
              className={`px-8 py-4 rounded font-black uppercase text-lg transition ${
                activeSection === 'lmu'
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              LE MANS ULTIMATE
            </button>
            <button
              onClick={() => setActiveSection('iracing')}
              className={`px-8 py-4 rounded font-black uppercase text-lg transition ${
                activeSection === 'iracing'
                  ? 'bg-purple-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              iRACING
            </button>
          </div>

          {/* Teams Grid */}
          {activeSection === 'acc' && (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {accTeam.map((driver, idx) => (
                <div key={idx} className="bg-white p-6 rounded-lg border-2 border-gray-200 hover:border-purple-600 transition">
                  <div className="aspect-square bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg mb-4 flex items-center justify-center">
                    <span className="text-3xl font-black text-white opacity-20">{driver.name[0]}</span>
                  </div>
                  <div className="text-sm text-purple-600 font-bold mb-1">{driver.name}</div>
                  <div className="font-black text-gray-900 mb-2">{driver.lastName}</div>
                  <div className="text-2xl">{driver.country}</div>
                </div>
              ))}
            </div>
          )}

          {activeSection === 'lmu' && (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {lmuTeam.map((driver, idx) => (
                <div key={idx} className="bg-white p-6 rounded-lg border-2 border-gray-200 hover:border-purple-600 transition">
                  <div className="aspect-square bg-gradient-to-br from-pink-600 to-purple-600 rounded-lg mb-4 flex items-center justify-center">
                    <span className="text-3xl font-black text-white opacity-20">{driver.name[0]}</span>
                  </div>
                  <div className="text-sm text-purple-600 font-bold mb-1">{driver.name}</div>
                  <div className="font-black text-gray-900 mb-2">{driver.lastName}</div>
                  <div className="text-2xl">{driver.country}</div>
                </div>
              ))}
            </div>
          )}

          {activeSection === 'iracing' && (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {iracingTeam.map((driver, idx) => (
                <div key={idx} className="bg-white p-6 rounded-lg border-2 border-gray-200 hover:border-purple-600 transition">
                  <div className="aspect-square bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg mb-4 flex items-center justify-center">
                    <span className="text-3xl font-black text-white opacity-20">{driver.name[0]}</span>
                  </div>
                  <div className="text-sm text-purple-600 font-bold mb-1">{driver.name}</div>
                  <div className="font-black text-gray-900 mb-2">{driver.lastName}</div>
                  <div className="text-2xl">{driver.country}</div>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* Partners Section */}
      <section id="partners" className="py-24 px-6 bg-gray-50">
        <div className="max-w-6xl mx-auto text-center">
          <h2 className="text-5xl md:text-6xl font-black mb-6 text-gray-900 uppercase italic">
            PARTNERS
          </h2>
          <div className="w-24 h-1 bg-gradient-to-r from-purple-600 to-pink-600 mx-auto mb-16"></div>
          
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="bg-white rounded-lg p-8 border-2 border-gray-200 flex items-center justify-center min-h-[150px]">
                <span className="text-gray-400 font-bold text-center">LOGO HERE</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Merchandise Section */}
      <section className="py-24 px-6">
        <div className="max-w-6xl mx-auto text-center">
          <div className="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-12 text-white">
            <h2 className="text-5xl font-black uppercase italic mb-6">
              GET YOUR XCLUSIVE MERCHANDISE
            </h2>
            <p className="text-xl mb-8">Represent the pride. Wear the purple.</p>
            <a 
              href="https://raven.gg/stores/xclusive-esports/"
              target="_blank"
              className="inline-block bg-white text-purple-600 px-8 py-4 rounded font-black uppercase text-lg hover:bg-gray-100 transition"
            >
              SHOP NOW
            </a>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12 px-6">
        <div className="max-w-6xl mx-auto">
          <div className="grid md:grid-cols-3 gap-12 mb-12">
            <div>
              <img src="/logo.png" alt="XCLusive" className="h-16 mb-4" />
              <p className="text-gray-400">
                Dominating sim racing from console to PC. Join the pride.
              </p>
            </div>
            
            <div>
              <h3 className="text-white font-black mb-4 text-lg uppercase">QUICK LINKS</h3>
              <div className="space-y-2">
                <a href="/#about" className="block text-gray-400 hover:text-purple-400 transition font-bold uppercase">About Us</a>
                <a href="/#teams" className="block text-gray-400 hover:text-purple-400 transition font-bold uppercase">Teams</a>
                <a href="/race" className="block text-gray-400 hover:text-purple-400 transition font-bold uppercase">Race</a>
              </div>
            </div>
            
            <div>
              <h3 className="text-white font-black mb-4 text-lg uppercase">CONNECT</h3>
              <div className="flex gap-4 mb-4">
                <a href="https://discord.gg/AHNTFY9Uuu" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  Discord
                </a>
                <a href="https://www.instagram.com/xclusive_esport/" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  Instagram
                </a>
                <a href="https://www.youtube.com/@XCL_TV" target="_blank" className="text-gray-400 hover:text-purple-400 transition">
                  YouTube
                </a>
              </div>
            </div>
          </div>
          
          <div className="border-t border-gray-700 pt-8 text-center text-gray-400 text-sm">
            <p>&copy; 2026 XCLusive Gaming Events. All rights reserved. The lion is born to dominate.</p>
          </div>
        </div>
      </footer>

      <SignUpModal 
        isOpen={signUpOpen} 
        onClose={() => setSignUpOpen(false)} 
        onSuccess={() => {}}
      />

      <style jsx global>{`
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');
        
        * {
          font-family: 'Poppins', sans-serif;
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
