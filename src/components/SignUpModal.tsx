'use client';

import { useState } from 'react';
import { supabase } from '@/lib/supabase';

interface SignUpModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export default function SignUpModal({ isOpen, onClose, onSuccess }: SignUpModalProps) {
  const [step, setStep] = useState(1); // 1: Platform select, 2: Details
  const [platform, setPlatform] = useState<'steam' | 'ps5' | 'xbox' | null>(null);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    username: '',
    country: '',
    platformId: '',
    team: '',
  });

  const handlePlatformSelect = (selectedPlatform: 'steam' | 'ps5' | 'xbox') => {
    setPlatform(selectedPlatform);
    setStep(2);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const { data, error } = await supabase
        .from('users')
        .insert([
          {
            username: formData.username,
            country: formData.country,
            platform: platform,
            platform_id: formData.platformId,
            team: formData.team,
            elo_acc: 1200,
            elo_lmu: 1200,
            elo_iracing: 1200,
          },
        ])
        .select()

      if (error) throw error

      // Store user data in localStorage for now
      if (data && data[0]) {
        localStorage.setItem('xclUser', JSON.stringify(data[0]))
        onSuccess()
        onClose()
      }
    } catch (error) {
      console.error('Sign up error:', error)
      alert('Error signing up. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-md w-full p-8">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-3xl font-black text-gray-900 uppercase italic">
            {step === 1 ? 'CHOOSE PLATFORM' : 'CREATE PROFILE'}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700"
          >
            ✕
          </button>
        </div>

        {step === 1 ? (
          <div className="space-y-4">
            <button
              onClick={() => handlePlatformSelect('steam')}
              className="w-full p-4 border-2 border-purple-600 hover:bg-purple-50 rounded-lg font-bold uppercase transition"
            >
              🖥️ STEAM
            </button>
            <button
              onClick={() => handlePlatformSelect('ps5')}
              className="w-full p-4 border-2 border-blue-600 hover:bg-blue-50 rounded-lg font-bold uppercase transition"
            >
              🎮 PLAYSTATION 5
            </button>
            <button
              onClick={() => handlePlatformSelect('xbox')}
              className="w-full p-4 border-2 border-green-600 hover:bg-green-50 rounded-lg font-bold uppercase transition"
            >
              🎮 XBOX SERIES X/S
            </button>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-bold text-gray-900 uppercase mb-2">
                {platform === 'steam' ? 'Steam ID' : platform === 'ps5' ? 'PSN Username' : 'Xbox Gamertag'}
              </label>
              <input
                type="text"
                required
                value={formData.platformId}
                onChange={(e) => setFormData({ ...formData, platformId: e.target.value })}
                placeholder={platform === 'steam' ? 'Your Steam ID' : 'Your username'}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600"
              />
            </div>

            <div>
              <label className="block text-sm font-bold text-gray-900 uppercase mb-2">
                Display Name
              </label>
              <input
                type="text"
                required
                value={formData.username}
                onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                placeholder="Your display name"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600"
              />
            </div>

            <div>
              <label className="block text-sm font-bold text-gray-900 uppercase mb-2">
                Country
              </label>
              <input
                type="text"
                required
                value={formData.country}
                onChange={(e) => setFormData({ ...formData, country: e.target.value })}
                placeholder="Your country"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600"
              />
            </div>

            <div>
              <label className="block text-sm font-bold text-gray-900 uppercase mb-2">
                Team (Optional)
              </label>
              <input
                type="text"
                value={formData.team}
                onChange={(e) => setFormData({ ...formData, team: e.target.value })}
                placeholder="Your team"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600"
              />
            </div>

            <div className="flex gap-4 pt-4">
              <button
                type="button"
                onClick={() => setStep(1)}
                className="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-900 font-bold uppercase rounded-lg hover:bg-gray-50 transition"
              >
                BACK
              </button>
              <button
                type="submit"
                disabled={loading}
                className="flex-1 px-4 py-2 bg-purple-600 text-white font-bold uppercase rounded-lg hover:bg-purple-700 transition disabled:opacity-50"
              >
                {loading ? 'CREATING...' : 'CREATE PROFILE'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
