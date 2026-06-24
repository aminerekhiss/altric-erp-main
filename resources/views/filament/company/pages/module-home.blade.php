<x-filament-panels::page>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>

    <script>
        window.moduleLauncherData = function (modules) {
            return {
                query: '',
                activeCategory: 'All',
                modules: Array.isArray(modules) ? modules : [],

                categories: ['All', 'Core', 'Operations', 'Finance', 'People', 'System', 'Other'],

                filteredModules() {
                    const q = (this.query || '').toLowerCase().trim();

                    return this.modules.filter(m => {
                        const matchCategory =
                            this.activeCategory === 'All' ||
                            m.category === this.activeCategory;

                        const matchSearch =
                            !q ||
                            `${m.label} ${m.description}`.toLowerCase().includes(q);

                        return matchCategory && matchSearch;
                    });
                }
            };
        };
    </script>

    @php
        function normalize($text) {
            return iconv('UTF-8', 'ASCII//TRANSLIT', strtolower(trim($text)));
        }

        $moduleIcons = [
            'ventes' => 'mdi:cart',
            'facture steg' => 'mdi:receipt-text',
            'entreprises' => 'mdi:office-building',
            'ressources humaines' => 'mdi:account-group',
            'produits et stock' => 'mdi:package-variant',
            'tickets' => 'mdi:ticket-confirmation',
            'achats' => 'mdi:truck-delivery',
            'comptabilite' => 'mdi:calculator',
            'finances' => 'mdi:cash-multiple',
            'services bancaires' => 'mdi:bank',
            'rapports' => 'mdi:chart-bar',
            'settings' => 'mdi:cog',
        ];

        $moduleCategories = [
            'Ventes' => 'Core',
            'Facture STEG' => 'Core',
            'Entreprises' => 'People',
            'Ressources Humaines' => 'People',
            'Produits et stock' => 'Operations',
            'Tickets' => 'Operations',
            'Achats' => 'Finance',
            'Comptabilité' => 'Finance',
            'Finances' => 'Finance',
            'Services bancaires' => 'Finance',
            'Rapports' => 'System',
            'Settings' => 'System',
        ];

        $colors = [
            'Core' => 'from-indigo-500 to-indigo-600',
            'Operations' => 'from-emerald-500 to-emerald-600',
            'Finance' => 'from-amber-500 to-amber-600',
            'People' => 'from-pink-500 to-rose-500',
            'System' => 'from-slate-500 to-slate-700',
            'Other' => 'from-gray-400 to-gray-500',
        ];

        $modulesUi = collect($modules)->map(function ($module) use ($moduleIcons, $moduleCategories, $colors) {

            $label = $module['label'] ?? 'Module';
            $key = normalize($label);

            $category = $moduleCategories[$label] ?? 'Other';

            return [
                ...$module,
                'icon' => $moduleIcons[$key] ?? 'mdi:apps',
                'category' => $category,
                'color' => $colors[$category] ?? $colors['Other'],
            ];
        })->values()->all();
    @endphp

    <style>
        .app-tile {
            text-align: center;
            padding: 20px 10px;
            border-radius: 16px;
            transition: 0.25s;
        }

        .app-tile:hover {
            transform: translateY(-6px) scale(1.05);
        }

        .app-icon-box {
            width: 90px;
            height: 90px;
            margin: auto;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .app-icon {
            font-size: 40px;
            color: white;
        }

        .category-pill {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            border: 1px solid #d1d5db;
            background: white;
            transition: 0.2s;
        }

        .category-pill.active {
            background: #5b52a3;
            color: white;
            border-color: #5b52a3;
        }
    </style>

    <div x-data="moduleLauncherData(@js($modulesUi))" class="p-6">

        <!-- HEADER -->
        

        <!-- SEARCH -->
        <div class="max-w-md mx-auto mb-4">
            <input x-model="query"
                   placeholder="Search apps..."
                   class="w-full border px-4 py-2 rounded-lg text-center">
        </div>

        <!-- CATEGORY PILLS 🔥 -->
        <div class="flex justify-center flex-wrap gap-2 mb-6">
            <template x-for="category in categories" :key="category">
                <button @click="activeCategory = category"
                        class="category-pill"
                        :class="activeCategory === category ? 'active' : ''"
                        x-text="category">
                </button>
            </template>
        </div>

        <!-- GRID -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-6">

            <template x-for="module in filteredModules()" :key="module.label">
                <a :href="module.url" class="app-tile">

                    <div class="app-icon-box"
                         :class="'bg-gradient-to-br ' + module.color">
                        <iconify-icon :icon="module.icon" class="app-icon"></iconify-icon>
                    </div>

                    <div class="text-sm font-semibold" x-text="module.label"></div>

                </a>
            </template>

        </div>

        <!-- EMPTY -->
        <div x-show="filteredModules().length === 0"
             class="text-center mt-6 text-gray-500">
            No apps found
        </div>

    </div>
</x-filament-panels::page>