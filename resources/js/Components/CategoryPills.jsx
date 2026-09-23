import React from 'react';
import { LayoutGrid, Cherry, Radio, Rocket, Fish, Flame, ChevronRight } from 'lucide-react';

const categories = [['all', 'Casino lobby', 'A world of play', LayoutGrid], ['slots', 'Slots', 'Find your favorite', Cherry], ['live', 'Live casino', 'Take your seat', Radio], ['crash', 'Crash & fast', 'Feel the rush', Rocket], ['fishing', 'Fish hunter', 'Dive into the action', Fish], ['popular', 'Popular', 'The crowd favorites', Flame]];
export default function CategoryPills({ selectedCategory, onSelectCategory }) {
    return <nav className="nw-categories" aria-label="Game categories">{categories.map(([id, label, caption, Icon]) => <button key={id} aria-pressed={selectedCategory === id} onClick={() => onSelectCategory(id)} className={`nw-category nw-category-${id} ${selectedCategory === id ? 'is-active' : ''}`}><span className="nw-category-icon"><Icon size={24} strokeWidth={1.6} /></span><span><strong>{label}</strong><small>{caption}</small></span><ChevronRight className="nw-category-arrow" size={15} /></button>)}</nav>;
}
