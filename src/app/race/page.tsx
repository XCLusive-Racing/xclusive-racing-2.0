'use client';

import { useState } from 'react';
import Link from 'next/link';
import Header from '@/components/Header';
import SignUpModal from '@/components/SignUpModal';

export default function Race() {
  const [signUpOpen, setSignUpOpen] = useState(false);
  const [selectedPlatform, setSelectedPlatform] = useState<'acc' | 'lmu' | 'iracing' | null>(null);

  const platforms = [
    {
      id: 'acc',
      name: 'ACC CONSOLE',
      description: 'Assetto Corsa Competizione on PlayStation 5 & Xbox Series X/S',
      color: 'purple',
      events: 12,
    },
    {
      id: 'lmu',
      name: 'LE MANS ULTIMATE',
      description: 'Le Mans Ultimate - Premium PC Sim Racing',
      color: 'pink',
      events: 8,
    },
    {
      id: 'iracing',
      name: 'iRACING',
      description: 'iRacing - World\'s Leading Online Racing Simulation',
      color: 'blue',
      events: 6,
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Header onSignUpClick={() => setSignUpOpen(true)} />
      
      <main className="pt-24 px-6 pb-12">
        <div className="max-w-6xl mx-auto">
          <div className="mb-12">
            <h1 className="text-5xl font-black text-gray-900 uppercase italic mb-4">
              RACE & EVENTS
            </h1>
            <p className="text-gray-600 text-lg">
              Choose your platform and find races to join
            </p>
          </div>

          {!selectedPlatform ? (
            <div className="grid md:grid-cols-3 gap-8 mb-12">
              {platforms.map((platform) => (
                <button
                  key={platform.id}
                  onClick={() => setSelectedPlatform(platform.id as any)}
                  className={`p-8 rounded-lg border-2 hover:shadow-lg transition text-left ${
                    platform.color === 'purple' ? 'border-purple-600 hover:bg-purple-50' :
                    platform.color === 'pink' ? 'border-pink-600 hover:bg-pink-50' :
                    'border-blue-600 hover:bg-blue-50'
                  }`}
                >
                  <div className={`text-3xl font-black uppercase italic mb-2 ${
                    platform.color === 'purple' ? 'text-purple-600' :
                    platform.color === 'pink' ? 'text-pink-600' :
                    'text-blue-600'
                  }`}>
                    {platform.name}
                  </div>
                  <p className="text-gray-600 mb-4">{platform.description}</p>
                  <div className={`text-sm font-bold ${
                    platform.color === 'purple' ? 'text-purple-600' :
                    platform.color === 'pink' ? 'text-pink-600' :
                    'text-blue-600'
                  }`}>
                    {platform.events} ACTIVE EVENTS
                  </div>
                </button>
              ))}
            </div>
          ) : (
            <div>
              <button
                onClick={() => setSelectedPlatform(null)}
                className="mb-8 text-purple-600 hover:text-purple-700 font-bold uppercase flex items-center gap-2"
              >
                ← BACK TO PLATFORMS
              </button>

              <div className="mb-12">
                <h2 className="text-4xl font-black text-gray-900 uppercase italic mb-6">
                  {selectedPlatform === 'acc' ? 'ACC CONSOLE' : 
                   selectedPlatform === 'lmu' ? 'LE MANS ULTIMATE' : 'iRACING'} EVENTS
                </h2>

                {/* Coming Soon Placeholder */}
                <div className="bg-white rounded-lg shadow-lg p-12 text-center">
                  <div className="text-6xl mb-4">🏁</div>
                  <h3 className="text-3xl font-black text-gray-900 uppercase italic mb-4">
                    COMING SOON
                  </h3>
                  <p className="text-gray-600 mb-8 text-lg">
                    Event system is under development. Check back soon!
                  </p>
                  <a
                    href="https://www.xboxcommunityleague.com"
                    target="_blank"
                    className="inline-block bg-purple-600 hover:bg-purple-700 text-white px-8 py-4 rounded-lg font-bold uppercase transition"
                  >
                    VIEW XCL EVENTS
                  </a>
                </div>
              </div>
            </div>
          )}

          {/* Sign Up CTA */}
          {!selectedPlatform && (
            <div className="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-12 text-center text-white">
              <h2 className="text-3xl font-black uppercase italic mb-4">
                READY TO RACE?
              </h2>
              <p className="mb-8 text-lg">Sign up now to access all events and track your ELO rating</p>
              <button
                onClick={() => setSignUpOpen(true)}
                className="bg-white text-purple-600 px-8 py-3 rounded-lg font-bold uppercase hover:bg-gray-100 transition"
              >
                CREATE PROFILE
              </button>
            </div>
          )}
        </div>
      </main>

      <SignUpModal 
        isOpen={signUpOpen} 
        onClose={() => setSignUpOpen(false)} 
        onSuccess={() => {}}
      />
    </div>
  );
}
