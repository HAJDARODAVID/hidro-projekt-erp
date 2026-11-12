{{--
    Alpine data (x-data="...") of the subcontractor hours tables (desktop + mobile): which subcontractors show
    their workers. Default: all collapsed. Kept in localStorage, so it stays after a refresh / another month.
    Inline (not a <script>), because Livewire doesn't run scripts again when the table is rendered new.
    Needs: $subIDs (the subcontractor IDs of the month, as strings). Use only single quotes in here.
--}}
{
    key: 'working-hours.subcontractor.open',
    ids: {{ \Illuminate\Support\Js::from($subIDs) }},
    open: [],
    init() { try { this.open = (JSON.parse(localStorage.getItem(this.key)) || []).map(String); } catch (e) { this.open = []; } },
    save() { try { localStorage.setItem(this.key, JSON.stringify(this.open)); } catch (e) {} },
    isOpen(id) { return this.open.includes(String(id)); },
    toggle(id) {
        id = String(id);
        this.open = this.isOpen(id) ? this.open.filter(x => x !== id) : [...this.open, id];
        this.save();
    },
    allOpen() { return this.ids.length > 0 && this.ids.every(id => this.isOpen(id)); },
    toggleAll() {
        const all = this.allOpen();
        this.open = all ? this.open.filter(x => !this.ids.includes(x)) : [...new Set([...this.open, ...this.ids])];
        this.save();
    },
}
