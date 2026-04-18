document.addEventListener('alpine:init', function() {
  Alpine.data('dashboardWidget', function() {
    return {
      editMode: false,
      draggedWidgetId: null,
      widgets: [],
      availableWidgets: [],
      sizeOptions: {},

      init() {
        try {
          this.widgets = this.$el.dataset.widgets ? JSON.parse(this.$el.dataset.widgets) : [];
          this.availableWidgets = this.$el.dataset.available ? JSON.parse(this.$el.dataset.available) : [];
          this.sizeOptions = this.$el.dataset.sizes ? JSON.parse(this.$el.dataset.sizes) : {};
        } catch (e) {
          console.error('Dashboard init error:', e);
        }
      },

      toggleEditMode() {
        this.editMode = !this.editMode;
        if (!this.editMode) {
          this.draggedWidgetId = null;
        }
      },

      updatePositions() {
        const items = this.$refs.widgetGrid.querySelectorAll('.widget-item');
        this.widgets = Array.from(items).map((item, index) => {
          const widgetId = item.dataset.widgetId;
          const existing = this.widgets.find(w => w.widget_id === widgetId);
          return existing ? { ...existing, position: index } : { widget_id: widgetId, position: index };
        });
      },

      onDragStart(event, widgetId) {
        if (!this.editMode) {
          event.preventDefault();
          return;
        }

        this.draggedWidgetId = widgetId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', widgetId);
        event.currentTarget.classList.add('opacity-50');
      },

      onDragOver(event) {
        if (!this.editMode || !this.draggedWidgetId) {
          return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
      },

      onDrop(event, widgetId) {
        if (!this.editMode || !this.draggedWidgetId || this.draggedWidgetId === widgetId) {
          return;
        }

        const grid = this.$refs.widgetGrid;
        const dragged = grid.querySelector(`[data-widget-id="${this.draggedWidgetId}"]`);
        const target = grid.querySelector(`[data-widget-id="${widgetId}"]`);

        if (!dragged || !target) {
          return;
        }

        const rect = target.getBoundingClientRect();
        const shouldInsertAfter = event.clientY > rect.top + (rect.height / 2);

        if (shouldInsertAfter) {
          target.after(dragged);
        } else {
          target.before(dragged);
        }

        this.updatePositions();
        this.draggedWidgetId = null;
      },

      onDragEnd(event) {
        this.draggedWidgetId = null;

        if (event?.currentTarget) {
          event.currentTarget.classList.remove('opacity-50');
        }
      },

      async saveWidgets() {
        try {
          const response = await fetch('/admin/dashboard/save', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': this.getCsrfToken()
            },
            body: JSON.stringify({ widgets: this.widgets })
          });

          const data = await response.json();
          if (data.success) {
            this.editMode = false;
            window.location.reload();
          }
        } catch (error) {
          console.error('Save error:', error);
        }
      },

      addWidget(widgetId) {
        const widgetDef = this.availableWidgets[widgetId];
        if (!widgetDef) return;

        this.widgets.push({
          widget_id: widgetId,
          widget_type: 'system',
          title: widgetDef.name,
          position: this.widgets.length,
          width: widgetDef.size || 'medium',
          enabled: true,
          config: {}
        });
      },

      removeWidget(widgetId) {
        this.widgets = this.widgets.filter(w => w.widget_id !== widgetId);
      },

      async refreshWidget(widgetId) {
        const widgetEl = document.querySelector(`[data-widget-id="${widgetId}"] .widget-content`);
        if (!widgetEl) return;

        try {
          widgetEl.innerHTML = '<div class="p-4 text-center text-slate-400">Refreshing...</div>';

          const response = await fetch(`/admin/dashboard/widget/${widgetId}/refresh`);
          const data = await response.json();

          widgetEl.innerHTML = data.success
            ? this.renderWidgetContent(data.card || {}, widgetId)
            : `<div class="p-4 text-red-400">${data.message}</div>`;
        } catch (error) {
          widgetEl.innerHTML = `<div class="p-4 text-red-400">Error</div>`;
        }
      },

      async resetDashboard() {
        if (!confirm('Reset dashboard to default widgets?')) return;

        try {
          const response = await fetch('/admin/dashboard/reset', {
            method: 'POST',
            headers: { 'X-CSRF-Token': this.getCsrfToken() }
          });

          if (response.ok) window.location.reload();
        } catch (error) {
          console.error('Reset error:', error);
        }
      },

      getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
          return meta.getAttribute('content') || '';
        }

        const input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
      },

      getWidthClass(width) {
        return {
          'small': 'col-span-1',
          'medium': 'col-span-1',
          'large': 'col-span-1'
        }[width] || 'col-span-1';
      },

      renderWidgetContent(card, widgetId) {
        if (!card || typeof card !== 'object') {
          return '<div class="p-4 text-slate-400 dark:text-slate-500">No widget data available</div>';
        }

        const config = {
          app_status: {
            icon: 'server',
            labelTone: 'text-emerald-100/75',
            iconTone: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100',
            bar: 'bg-gradient-to-r from-cyan-300 via-emerald-300 to-cyan-300',
            metricTone: 'bg-gradient-to-r from-emerald-300 via-cyan-300 to-emerald-300',
            metricWidth: '82%',
            metricFallbackBars: ['100%', '86%', '72%', '58%'],
            footerTone: 'border-emerald-400/10 bg-emerald-400/5',
            footerLabel: 'Live',
            footerLabelTone: 'text-emerald-200/80',
            footerDot: 'bg-emerald-300',
            footerText: 'text-slate-300',
            metricBars: ['bg-emerald-300/80', 'bg-emerald-300/60', 'bg-emerald-300/40', 'bg-emerald-300/20'],
            valueFallback: 'MarwaPHP',
            metaFallback: 'Production',
            statusFallback: 'Active',
          },
          runtime_info: {
            icon: 'cpu',
            labelTone: 'text-violet-100/75',
            iconTone: 'border-violet-400/20 bg-violet-400/10 text-violet-100',
            bar: 'bg-gradient-to-r from-violet-300 via-cyan-300 to-violet-300',
            metricTone: 'bg-gradient-to-r from-violet-300 via-cyan-300 to-violet-300',
            metricWidth: '68%',
            metricFallbackBars: ['90%', '76%', '62%', '48%'],
            footerTone: 'border-violet-400/10 bg-violet-400/5',
            footerLabel: 'Server',
            footerLabelTone: 'text-violet-200/80',
            footerDot: 'bg-violet-300',
            footerText: 'text-slate-300',
            metricBars: ['bg-violet-300/80', 'bg-violet-300/60', 'bg-violet-300/40', 'bg-violet-300/20'],
            valueFallback: 'PHP',
            metaFallback: 'CLI',
          },
          memory_usage: {
            icon: 'memory-stick',
            labelTone: 'text-amber-100/75',
            iconTone: 'border-amber-400/20 bg-amber-400/10 text-amber-100',
            bar: 'bg-gradient-to-r from-amber-300 via-orange-300 to-amber-300',
            metricTone: 'bg-gradient-to-r from-amber-300 via-orange-300 to-amber-300',
            metricWidth: '76%',
            metricFallbackBars: ['82%', '68%', '54%', '40%'],
            footerTone: 'border-amber-400/10 bg-amber-400/5',
            footerLabel: 'Capacity',
            footerLabelTone: 'text-amber-200/80',
            footerDot: 'bg-amber-300',
            footerText: 'text-amber-50',
            metricBars: ['bg-amber-300/80', 'bg-amber-300/60', 'bg-amber-300/40', 'bg-amber-300/20'],
            valueFallback: '--',
            metaFallback: 'System',
          },
          disk_space: {
            icon: 'hard-drive',
            labelTone: 'text-emerald-100/75',
            iconTone: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100',
            bar: 'bg-gradient-to-r from-emerald-300 via-cyan-300 to-emerald-300',
            metricTone: 'bg-gradient-to-r from-emerald-300 via-cyan-300 to-emerald-300',
            metricWidth: '74%',
            metricFallbackBars: ['88%', '74%', '60%', '46%'],
            footerTone: 'border-emerald-400/10 bg-emerald-400/5',
            footerLabel: 'Space',
            footerLabelTone: 'text-emerald-200/80',
            footerDot: 'bg-emerald-300',
            footerText: 'text-emerald-50',
            metricBars: ['bg-emerald-300/80', 'bg-emerald-300/80', 'bg-emerald-300/60', 'bg-emerald-300/30'],
            valueFallback: 'Storage',
            metaFallback: 'Available',
          },
          load_average: {
            icon: 'gauge',
            labelTone: 'text-cyan-100/75',
            iconTone: 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100',
            bar: 'bg-gradient-to-r from-cyan-300 via-sky-300 to-cyan-300',
            metricTone: 'bg-gradient-to-r from-cyan-300 via-sky-300 to-cyan-300',
            metricWidth: '58%',
            metricFallbackBars: ['70%', '56%', '42%'],
            footerTone: 'border-cyan-400/10 bg-cyan-400/5',
            footerLabel: 'Load',
            footerLabelTone: 'text-cyan-200/80',
            footerDot: 'bg-cyan-300',
            footerText: 'text-cyan-50',
            metricBars: ['bg-cyan-300/80', 'bg-cyan-300/60', 'bg-cyan-300/30'],
            valueFallback: '--',
            metaFallback: 'System',
          },
          theme_info: {
            icon: 'palette',
            labelTone: 'text-fuchsia-100/75',
            iconTone: 'border-fuchsia-400/20 bg-fuchsia-400/10 text-fuchsia-100',
            bar: 'bg-gradient-to-r from-fuchsia-300 via-purple-300 to-fuchsia-300',
            metricTone: 'bg-gradient-to-r from-fuchsia-300 via-purple-300 to-fuchsia-300',
            metricWidth: '66%',
            metricFallbackBars: ['84%', '70%', '56%', '42%'],
            footerTone: 'border-purple-400/10 bg-purple-400/5',
            footerLabel: 'Skin',
            footerLabelTone: 'text-purple-200/80',
            footerDot: 'bg-purple-300',
            footerText: 'text-purple-50',
            metricBars: ['bg-fuchsia-300/80', 'bg-purple-300/70', 'bg-fuchsia-300/50', 'bg-purple-300/30'],
            valueFallback: 'Admin',
            metaFallback: 'Theme',
          },
        };

        const theme = config[widgetId] || config.app_status;
        const metricBarWidths = Array.isArray(card.metric_bars) && card.metric_bars.length
          ? card.metric_bars
          : (theme.metricFallbackBars || []);
        const metricBars = metricBarWidths
          .map((barWidth) => `<div class="h-1.5 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full ${theme.metricTone}" style="width: ${this.escapeHtml(barWidth)};"></span></div>`)
          .join('');
        const label = this.escapeHtml(card.label || this.defaultWidgetLabel(widgetId));
        const value = this.escapeHtml(card.value || theme.valueFallback || '');
        const meta = this.escapeHtml(card.meta || theme.metaFallback || '');
        const status = this.escapeHtml(card.status || theme.statusFallback || '');
        const iconMarkup = this.renderIcon(theme.icon, 'h-5 w-5');
        const barCount = Math.max(3, metricBarWidths.length || 0);
        const metricWidth = this.escapeHtml(card.metric_width || theme.metricWidth || '66%');

        if (widgetId === 'theme_info') {
          return [
            '<div class="space-y-4">',
            `  <div class="h-1.5 rounded-full ${theme.bar}"></div>`,
            '  <div class="flex items-start gap-3">',
            `    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border ${theme.iconTone}">`,
            `      ${iconMarkup}`,
            '    </span>',
            '    <div class="space-y-1">',
            `      <p class="text-[0.65rem] font-extrabold uppercase tracking-[0.18em] ${theme.labelTone}">${label}</p>`,
            `      <div class="text-2xl font-black tracking-tight text-white">${value}</div>`,
            '    </div>',
            '  </div>',
            `  <div class="flex items-center justify-between gap-3 rounded-2xl border ${theme.footerTone} px-3 py-2">`,
            `    <div class="inline-flex items-center gap-2 text-xs ${theme.footerText}"><span class="h-2 w-2 rounded-full ${theme.footerDot}"></span><span>${meta}</span></div>`,
            `    <span class="text-[0.65rem] font-bold uppercase tracking-[0.18em] ${theme.footerLabelTone}">${theme.footerLabel}</span>`,
            '  </div>',
            '  <div class="space-y-2">',
            '    <div class="h-2 overflow-hidden rounded-full bg-white/10">',
            `      <span class="block h-full rounded-full ${theme.metricTone}" style="width: ${metricWidth};"></span>`,
            '    </div>',
            '  </div>',
            '  <div class="grid gap-1" style="grid-template-columns: repeat(4, minmax(0, 1fr));">',
            metricBars || '    <div class="h-1.5 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full bg-white/20" style="width: 100%;"></span></div>',
            '  </div>',
            '</div>'
          ].join('');
        }

        if (widgetId === 'app_status') {
          return [
            '<div class="space-y-4">',
            `  <div class="h-1.5 rounded-full ${theme.bar}"></div>`,
            '  <div class="flex items-start justify-between gap-3">',
            '    <div class="flex items-start gap-3">',
            `      <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border ${theme.iconTone}">`,
            `        ${iconMarkup}`,
            '      </span>',
            '      <div class="space-y-1">',
            `        <p class="text-[0.65rem] font-extrabold uppercase tracking-[0.18em] ${theme.labelTone}">${label}</p>`,
            `        <div class="text-2xl font-black tracking-tight text-white">${value}</div>`,
            '      </div>',
            '    </div>',
            `    <span class="inline-flex items-center rounded-full border ${theme.iconTone} px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.14em] ${theme.footerLabelTone}">${status}</span>`,
            '  </div>',
            `  <div class="flex items-center justify-between gap-3 rounded-2xl border ${theme.footerTone} px-3 py-2">`,
            `    <div class="inline-flex items-center gap-2 text-xs ${theme.footerText}"><span class="h-2 w-2 rounded-full ${theme.footerDot}"></span><span>${meta}</span></div>`,
            `    <span class="text-[0.65rem] font-bold uppercase tracking-[0.18em] ${theme.footerLabelTone}">${theme.footerLabel}</span>`,
            '  </div>',
            '  <div class="space-y-2">',
            '    <div class="h-2 overflow-hidden rounded-full bg-white/10">',
            `      <span class="block h-full rounded-full ${theme.metricTone}" style="width: ${metricWidth};"></span>`,
            '    </div>',
            '  </div>',
            '  <div class="grid gap-1" style="grid-template-columns: repeat(4, minmax(0, 1fr));">',
            metricBars || '    <div class="h-1.5 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full bg-white/20" style="width: 100%;"></span></div>',
            '  </div>',
            '</div>'
          ].join('');
        }

        return [
          '<div class="space-y-4">',
          `  <div class="h-1.5 rounded-full ${theme.bar}"></div>`,
          '  <div class="flex items-start gap-3">',
          `    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border ${theme.iconTone}">`,
          `      ${iconMarkup}`,
          '    </span>',
          '    <div class="space-y-1">',
          `      <p class="text-[0.65rem] font-extrabold uppercase tracking-[0.18em] ${theme.labelTone}">${label}</p>`,
          `      <div class="text-2xl font-black tracking-tight text-white">${value}</div>`,
          '    </div>',
          '  </div>',
          `  <div class="flex items-center justify-between gap-3 rounded-2xl border ${theme.footerTone} px-3 py-2">`,
          `    <div class="inline-flex items-center gap-2 text-xs ${theme.footerText}"><span class="h-2 w-2 rounded-full ${theme.footerDot}"></span><span>${meta}</span></div>`,
          `    <span class="text-[0.65rem] font-bold uppercase tracking-[0.18em] ${theme.footerLabelTone}">${theme.footerLabel}</span>`,
          '  </div>',
          '  <div class="space-y-2">',
          '    <div class="h-2 overflow-hidden rounded-full bg-white/10">',
          `      <span class="block h-full rounded-full ${theme.metricTone}" style="width: ${metricWidth};"></span>`,
          '    </div>',
          '  </div>',
          '  <div class="grid gap-1" style="grid-template-columns: repeat(4, minmax(0, 1fr));">',
          metricBars || '    <div class="h-1.5 overflow-hidden rounded-full bg-white/10"><span class="block h-full rounded-full bg-white/20" style="width: 100%;"></span></div>',
          '  </div>',
          '</div>'
        ].join('');
      },

      renderIcon(name, className) {
        const safeName = this.escapeHtml(name);
        const safeClass = this.escapeHtml(className);
        const spriteUrl = '/themes/admin/assets/icons/lucide.svg';

        return `<svg class="${safeClass}" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><use href="${spriteUrl}#${safeName}"></use></svg>`;
      },

      defaultWidgetLabel(widgetId) {
        const map = {
          app_status: 'Application',
          runtime_info: 'Runtime',
          memory_usage: 'Memory',
          disk_space: 'Disk',
          load_average: 'Load',
          theme_info: 'Theme'
        };

        return map[widgetId] || 'Widget';
      },

      toneClass(tone) {
        const map = {
          success: 'bg-emerald-400/10 text-emerald-400',
          warning: 'bg-amber-400/10 text-amber-300',
          primary: 'bg-cyan-400/10 text-cyan-300',
          neutral: 'bg-slate-400/10 text-slate-300'
        };

        return map[tone] || map.neutral;
      },

      escapeHtml(value) {
        return String(value)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }
    };
  });
});
