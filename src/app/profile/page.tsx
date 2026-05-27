'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import Header from '@/components/Header';
import SignUpModal from '@/components/SignUpModal';

interface User {
  id: string;
  username: string;
  country: string;
  platform: string;
  elo_acc: number;
  elo_lmu: number;
  elo_iracing: number;
  team: string;
}

export default function Profile() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [signUpOpen, setSignUpOpen] = useState(false);

  useEffect(() => {
    const storedUser = localStorage.getItem('xclUser');
    if (storedUser) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  if (loading) {
    return <div className="flex items-center justify-center min-h-screen">Loading...</div>;
  }

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header onSignUpClick={() => setSignUpOpen(true)} />
        <div className="pt-24 flex items-center justify-center min-h-[80vh]">
          <div className="text-center">
            <h1 className="text-4xl font-black mb-4 text-gray-900">NO PROFILE FOUND</h1>
            <p className="text-gray-600 mb-8">Sign up to create your XCLusive Racing profile</p>
            <button
              onClick={() => setSignUpOpen(true)}
              className="bg-purple-600 hover:bg-purple-700 text-white px-8 py-4 rounded-lg font-bold uppercase transition"
            >
              SIGN UP NOW
            </button>
          </div>
        </div>
        <SignUpModal 
          isOpen={signUpOpen} 
          onClose={() => setSignUpOpen(false)} 
          onSuccess={() => {
            const storedUser = localStorage.getItem('xclUser');
            if (storedUser) {
              setUser(JSON.parse(storedUser));
            }
          }}
        />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header onSignUpClick={() => {}} />
      
      <main className="pt-24 px-6 pb-12">
        <div className="max-w-4xl mx-auto">
          {/* Profile Header */}
          <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
            <div className="flex items-center gap-6 mb-6">
              <div className="w-24 h-24 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center">
                <span className="text-4xl font-black text-white">{user.username[0]}</span>
              </div>
              <div>
                <h1 className="text-4xl font-black text-gray-900 uppercase italic mb-2">
                  {user.username}
                </h1>
                <p className="text-gray-600 uppercase tracking-wide">
                  {user.country} • {user.platform.toUpperCase()}
                </p>
                {user.team && (
                  <p className="text-purple-600 font-bold uppercase mt-2">{user.team}</p>
                )}
              </div>
            </div>
            <button className="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-bold uppercase transition">
              EDIT PROFILE
            </button>
          </div>

          {/* ELO Ratings */}
          <div className="grid md:grid-cols-3 gap-6 mb-8">
            <div className="bg-white rounded-lg shadow-lg p-6 border-t-4 border-purple-600">
              <h3 className="text-sm font-bold text-gray-600 uppercase tracking-wide mb-2">
                ACC CONSOLE
              </h3>
              <div className="text-4xl font-black text-purple-600 mb-2">
                {user.elo_acc}
              </div>
              <p className="text-gray-600 text-sm">Current Rating</p>
            </div>

            <div className="bg-white rounded-lg shadow-lg p-6 border-t-4 border-pink-600">
              <h3 className="text-sm font-bold text-gray-600 uppercase tracking-wide mb-2">
                LE MANS ULTIMATE
              </h3>
              <div className="text-4xl font-black text-pink-600 mb-2">
                {user.elo_lmu}
              </div>
              <p className="text-gray-600 text-sm">Current Rating</p>
            </div>

            <div className="bg-white rounded-lg shadow-lg p-6 border-t-4 border-blue-600">
              <h3 className="text-sm font-bold text-gray-600 uppercase tracking-wide mb-2">
                iRACING
              </h3>
              <div className="text-4xl font-black text-blue-600 mb-2">
                {user.elo_iracing}
              </div>
              <p className="text-gray-600 text-sm">Current Rating</p>
            </div>
          </div>

          {/* Race or Events Section */}
          <div className="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h2 className="text-3xl font-black text-gray-900 uppercase italic mb-6">
              NEXT STEPS
            </h2>
            <div className="grid md:grid-cols-2 gap-6">
              <Link 
                href="/race"
                className="p-6 border-2 border-purple-600 hover:bg-purple-50 rounded-lg transition"
              >
                <div className="text-2xl font-black text-purple-600 uppercase italic mb-2">
                  FIND RACES
                </div>
                <p className="text-gray-600">Browse and join upcoming racing events</p>
              </Link>

              <a 
                href="https://www.xboxcommunityleague.com"
                target="_blank"
                className="p-6 border-2 border-purple-600 hover:bg-purple-50 rounded-lg transition"
              >
                <div className="text-2xl font-black text-purple-600 uppercase italic mb-2">
                  XCL EVENTS
                </div>
                <p className="text-gray-600">View all XCL hosted events and championships</p>
              </a>
            </div>
          </div>

          {/* Stats Section */}
          <div className="bg-white rounded-lg shadow-lg p-8">
            <h2 className="text-3xl font-black text-gray-900 uppercase italic mb-6">
              YOUR STATS
            </h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
              <div className="p-4 bg-gray-50 rounded-lg">
                <div className="text-2xl font-black text-gray-900">0</div>
                <p className="text-sm text-gray-600 uppercase">Races</p>
              </div>
              <div className="p-4 bg-gray-50 rounded-lg">
                <div className="text-2xl font-black text-gray-900">0</div>
                <p className="text-sm text-gray-600 uppercase">Wins</p>
              </div>
              <div className="p-4 bg-gray-50 rounded-lg">
                <div className="text-2xl font-black text-gray-900">0</div>
                <p className="text-sm text-gray-600 uppercase">Podiums</p>
              </div>
              <div className="p-4 bg-gray-50 rounded-lg">
                <div className="text-2xl font-black text-gray-900">0%</div>
                <p className="text-sm text-gray-600 uppercase">Win Rate</p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
