document.addEventListener('alpine:init', function() {
  Alpine.data('dashboardWidget', function() {
    return {
      editMode: false,
      widgets: [],
      availableWidgets: [],
      availableWidgetMap: {},
      sizeOptions: {},
      draggedWidgetId: null,

      init() {
        try {
          this.widgets = this.$el.dataset.widgets ? JSON.parse(this.$el.dataset.widgets) : [];
          const available = this.$el.dataset.available ? JSON.parse(this.$el.dataset.available) : [];
          this.availableWidgetMap = Array.isArray(available) ? Object.fromEntries(available.map(widget => [widget.id, widget])) : available;
          this.availableWidgets = Array.isArray(available) ? available : Object.values(available);
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

      async saveWidgets() {
        try {
          const widgets = this.widgets.map((widget, index) => ({
            ...widget,
            position: index
          }));
          const response = await fetch('/admin/dashboard/save', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': this.getCsrfToken()
            },
            body: JSON.stringify({ widgets })
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
        this.insertWidgetAt(widgetId, this.widgets.length);
        this.saveWidgets();
      },

      removeWidget(widgetId) {
        this.widgets = this.widgets.filter(w => w.widget_id !== widgetId);
        this.saveWidgets();
      },

      startDrag(widgetId) {
        if (!this.editMode) {
          return;
        }

        this.draggedWidgetId = widgetId;
      },

      endDrag() {
        this.draggedWidgetId = null;
      },

      dropOnWidget(targetWidgetId) {
        if (!this.draggedWidgetId || this.draggedWidgetId === targetWidgetId) {
          this.endDrag();
          return;
        }

        const draggedIndex = this.widgetIndex(this.draggedWidgetId);
        const targetIndex = this.widgetIndex(targetWidgetId);

        if (draggedIndex < 0 || targetIndex < 0) {
          this.endDrag();
          return;
        }

        const [widget] = this.widgets.splice(draggedIndex, 1);
        const insertIndex = draggedIndex < targetIndex ? targetIndex - 1 : targetIndex;
        this.widgets.splice(insertIndex, 0, widget);
        this.endDrag();
        this.saveWidgets();
      },

      dropOnGrid() {
        if (!this.draggedWidgetId) {
          return;
        }

        const draggedIndex = this.widgetIndex(this.draggedWidgetId);

        if (draggedIndex < 0) {
          this.endDrag();
          return;
        }

        const [widget] = this.widgets.splice(draggedIndex, 1);
        this.widgets.push(widget);
        this.endDrag();
        this.saveWidgets();
      },

      widgetIndex(widgetId) {
        return this.widgets.findIndex(widget => widget.widget_id === widgetId);
      },

      insertWidgetAt(widgetId, index) {
        const widgetDef = this.availableWidgetMap[widgetId] || this.availableWidgets.find(widget => widget.id === widgetId);
        if (!widgetDef) return;

        const widget = {
          widget_id: widgetId,
          widget_type: 'system',
          title: widgetDef.name,
          position: index,
          width: widgetDef.size || 'medium',
          enabled: true,
          config: {}
        };

        const normalizedIndex = Math.max(0, Math.min(index, this.widgets.length));
        this.widgets.splice(normalizedIndex, 0, widget);
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
            valueFallback: 'MarwaPHP',
            statusFallback: 'Active',
          },
          runtime_info: {
            icon: 'cpu',
            labelTone: 'text-violet-100/75',
            iconTone: 'border-violet-400/20 bg-violet-400/10 text-violet-100',
            bar: 'bg-gradient-to-r from-violet-300 via-cyan-300 to-violet-300',
            valueFallback: 'PHP',
          },
          memory_usage: {
            icon: 'memory-stick',
            labelTone: 'text-amber-100/75',
            iconTone: 'border-amber-400/20 bg-amber-400/10 text-amber-100',
            bar: 'bg-gradient-to-r from-amber-300 via-orange-300 to-amber-300',
            valueFallback: '--',
          },
          disk_space: {
            icon: 'hard-drive',
            labelTone: 'text-emerald-100/75',
            iconTone: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100',
            bar: 'bg-gradient-to-r from-emerald-300 via-cyan-300 to-emerald-300',
            valueFallback: 'Storage',
          },
          load_average: {
            icon: 'gauge',
            labelTone: 'text-cyan-100/75',
            iconTone: 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100',
            bar: 'bg-gradient-to-r from-cyan-300 via-sky-300 to-cyan-300',
            valueFallback: '--',
          },
          theme_info: {
            icon: 'palette',
            labelTone: 'text-fuchsia-100/75',
            iconTone: 'border-fuchsia-400/20 bg-fuchsia-400/10 text-fuchsia-100',
            bar: 'bg-gradient-to-r from-fuchsia-300 via-purple-300 to-fuchsia-300',
            valueFallback: 'Admin',
          },
        };

        const theme = config[widgetId] || config.app_status;
        const labelTone = theme.labelTone;
        const iconTone = theme.iconTone;
        const label = this.escapeHtml(card.label || this.defaultWidgetLabel(widgetId));
        const value = this.escapeHtml(card.value || theme.valueFallback || '');
        const status = this.escapeHtml(card.status || theme.statusFallback || '');
        const iconMarkup = this.renderIcon(theme.icon, 'h-5 w-5');

        if (widgetId === 'theme_info') {
          return [
            '<div class="space-y-4">',
            `  <div class="h-1.5 rounded-full ${theme.bar}"></div>`,
            '  <div class="flex items-start gap-3">',
            `    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border ${iconTone}">`,
            `      ${iconMarkup}`,
            '    </span>',
            '    <div class="space-y-1">',
            `      <p class="dashboard-widget-label text-[0.65rem] font-extrabold uppercase tracking-[0.18em] ${labelTone}">${label}</p>`,
            `      <div class="dashboard-widget-value text-2xl font-black tracking-tight text-white">${value}</div>`,
            '    </div>',
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
            `      <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border ${iconTone}">`,
            `        ${iconMarkup}`,
            '      </span>',
            '      <div class="space-y-1">',
            `        <p class="dashboard-widget-label text-[0.65rem] font-extrabold uppercase tracking-[0.18em] ${labelTone}">${label}</p>`,
            `        <div class="dashboard-widget-value text-2xl font-black tracking-tight text-white">${value}</div>`,
            '      </div>',
            '    </div>',
            `    <span class="dashboard-widget-status inline-flex items-center rounded-full border ${iconTone} px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-[0.14em]">${status}</span>`,
            '  </div>',
            '</div>'
          ].join('');
        }

        return [
          '<div class="space-y-4">',
          `  <div class="h-1.5 rounded-full ${theme.bar}"></div>`,
          '  <div class="flex items-start gap-3">',
          `    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border ${iconTone}">`,
          `      ${iconMarkup}`,
          '    </span>',
          '    <div class="space-y-1">',
          `      <p class="dashboard-widget-label text-[0.65rem] font-extrabold uppercase tracking-[0.18em] ${labelTone}">${label}</p>`,
          `      <div class="dashboard-widget-value text-2xl font-black tracking-tight text-white">${value}</div>`,
          '    </div>',
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
