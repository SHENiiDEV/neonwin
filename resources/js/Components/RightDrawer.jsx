import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { 
    MessageSquare, 
    X, 
    Send, 
    Sparkles, 
    Flame, 
    Trophy, 
    Smile, 
    Coins,
    Shield
} from 'lucide-react';
import axios from 'axios';

export default function RightDrawer({ isOpen, onClose, initialMessages = [], highlights = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [activeTab, setActiveTab] = useState('chat'); // 'chat' | 'wins'
    const [room, setRoom] = useState('global');
    const [messages, setMessages] = useState(initialMessages.length > 0 ? initialMessages : [
        { id: 1, user_name: 'WildPapa442', vip_level: 3, message: 'Hey friends! Welcome to Neonwin! Good luck everyone today! 🚀', created_at: '14:42' },
        { id: 2, user_name: 'RichPiano', vip_level: 4, message: 'Just hit 120x on Sweet Bonanza! 🍬🔥', created_at: '14:43' },
        { id: 3, user_name: 'Arendsa', vip_level: 2, message: 'Nice hit mate! Good luck on the tournament leaderboard!', created_at: '14:45' },
        { id: 4, user_name: 'Killer729', vip_level: 5, message: 'Presale tokens looking solid ⚡ Let\'s go!', created_at: '14:48' },
    ]);
    const [newMessage, setNewMessage] = useState('');
    const [sending, setSending] = useState(false);

    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || sending) return;

        const text = newMessage.trim();
        setNewMessage('');
        setSending(true);

        try {
            const res = await axios.post('/chat/send', {
                message: text,
                room: room
            });

            if (res.data && res.data.message) {
                setMessages(prev => [...prev, res.data.message]);
            }
        } catch (err) {
            // Optimistic fallback
            setMessages(prev => [
                ...prev, 
                {
                    id: Date.now(),
                    user_name: user ? user.name : 'Guest_Player',
                    vip_level: user ? user.vip_level : 1,
                    message: text,
                    created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                }
            ]);
        } finally {
            setSending(false);
        }
    };

    const addEmoji = (emoji) => {
        setNewMessage(prev => prev + emoji);
    };

    return (
        <div role="dialog" aria-modal="true" aria-label="Community chat" style={{ maxWidth: '100vw' }} className={`
            fixed top-0 right-0 bottom-0 z-50 w-80 sm:w-96 bg-[#0E121C] border-l border-[#1E2638] shadow-2xl flex flex-col transition-transform duration-300 ease-in-out
            ${isOpen ? 'translate-x-0' : 'translate-x-full'}
        `}>
            
            {/* 1. Drawer Header */}
            <div className="h-18 px-4 flex items-center justify-between border-b border-[#1E2638] bg-[#0A0D15]">
                <div className="flex items-center gap-2">
                    <button
                        onClick={() => setActiveTab('chat')}
                        className={`px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition flex items-center gap-1.5 ${activeTab === 'chat' ? 'bg-purple-600 text-white shadow-neon-purple' : 'text-gray-400 hover:text-white'}`}
                    >
                        <MessageSquare className="w-3.5 h-3.5" />
                        <span>LIVE CHAT</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('wins')}
                        className={`px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition flex items-center gap-1.5 ${activeTab === 'wins' ? 'bg-cyan-600 text-white shadow-neon-cyan' : 'text-gray-400 hover:text-white'}`}
                    >
                        <Sparkles className="w-3.5 h-3.5 text-yellow-300" />
                        <span>LIVE WINS</span>
                    </button>
                </div>

                <button 
                    onClick={onClose} aria-label="Close community chat"
                    className="p-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition"
                >
                    <X className="w-4 h-4" />
                </button>
            </div>

            {/* 2. Chat View */}
            {activeTab === 'chat' && (
                <div className="flex-1 flex flex-col overflow-hidden">
                    {/* Room Selector */}
                    <div className="px-4 py-2 bg-[#0B0E14] border-b border-[#1A2234] flex items-center justify-between text-[11px] text-gray-400">
                        <div className="flex items-center gap-2 font-semibold">
                            <span className="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span>Room:</span>
                            <select 
                                value={room} 
                                onChange={(e) => setRoom(e.target.value)}
                                className="bg-[#141A28] text-white rounded px-2 py-0.5 border border-white/10 focus:outline-none"
                            >
                                <option value="global">🌐 Global Chat</option>
                                <option value="highrollers">💎 High Rollers</option>
                                <option value="vip">👑 VIP Lounge</option>
                            </select>
                        </div>
                        <span className="font-mono text-purple-400">1,482 Online</span>
                    </div>

                    {/* Messages Stream */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-3.5">
                        {messages.map((msg, idx) => (
                            <div key={msg.id || idx} className="space-y-1 group">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="w-6 h-6 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-[10px] font-bold text-white">
                                            {msg.user_name ? msg.user_name.charAt(0).toUpperCase() : 'U'}
                                        </div>
                                        <span className="text-xs font-bold text-gray-200">
                                            {msg.user_name}
                                        </span>
                                        <span className="px-1.5 py-0.2 rounded bg-purple-900/60 border border-purple-500/30 text-[9px] font-extrabold text-purple-300">
                                            LV {msg.vip_level || 1}
                                        </span>
                                    </div>
                                    <span className="text-[10px] text-gray-500">
                                        {msg.created_at || 'Just now'}
                                    </span>
                                </div>

                                <div className="p-2.5 rounded-2xl rounded-tl-sm bg-[#141A29] border border-[#222B3E] text-xs text-gray-300 leading-relaxed group-hover:border-purple-500/30 transition">
                                    {msg.message}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Quick Emojis */}
                    <div className="px-4 py-1.5 bg-[#0B0E14] border-t border-[#1A2234] flex items-center gap-1.5 text-sm">
                        {['🔥', '🚀', '💎', '🍬', '⚡', '👑', '🎉'].map(emoji => (
                            <button 
                                key={emoji} 
                                onClick={() => addEmoji(emoji)}
                                className="p-1 hover:bg-white/10 rounded transition"
                            >
                                {emoji}
                            </button>
                        ))}
                    </div>

                    {/* Input Bar */}
                    <form onSubmit={handleSendMessage} className="p-3 bg-[#0A0D15] border-t border-[#1E2638] flex items-center gap-2">
                        <input 
                            type="text"
                            value={newMessage}
                            onChange={(e) => setNewMessage(e.target.value)}
                            placeholder="Write a message..."
                            className="flex-1 bg-[#141A28] border border-[#222B3E] rounded-xl px-3 py-2 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition"
                        />
                        <button 
                            type="submit"
                            disabled={!newMessage.trim() || sending}
                            className="p-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 text-white transition shadow-neon-purple flex items-center justify-center"
                        >
                            <Send className="w-4 h-4" />
                        </button>
                    </form>
                </div>
            )}

            {/* 3. Live Wins Feed View */}
            {activeTab === 'wins' && (
                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                    <div className="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">
                        Real-time community payouts
                    </div>

                    {highlights.map((win, idx) => (
                        <div 
                            key={win.id || idx}
                            className="p-3 rounded-2xl bg-[#141A29] border border-[#222B3E] hover:border-cyan-500/40 transition flex items-center justify-between shadow-sm"
                        >
                            <div className="flex items-center gap-2.5">
                                <div className="w-8 h-8 rounded-xl bg-purple-950/80 border border-purple-500/30 flex items-center justify-center text-base">
                                    🎰
                                </div>
                                <div>
                                    <div className="text-xs font-bold text-white">{win.game_name}</div>
                                    <div className="text-[10px] text-gray-400">{win.user_name}</div>
                                </div>
                            </div>

                            <div className="text-right">
                                <div className="font-mono font-black text-xs text-emerald-400">
                                    +${Number(win.win_amount).toFixed(2)}
                                </div>
                                <div className="text-[10px] font-bold font-mono text-cyan-400">
                                    {Number(win.multiplier).toFixed(0)}x
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

        </div>
    );
}
