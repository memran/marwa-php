document.addEventListener('DOMContentLoaded', function() {
  if (window.Alpine) {
    Alpine.data('dashboardWidget', function() {
      return {
        editMode: false,
        sortable: null,
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
          
          if (this.editMode) {
            this.$nextTick(() => this.initSortable());
          } else {
            this.destroySortable();
          }
        },

        initSortable() {
          const gridEl = this.$refs.widgetGrid;
          if (!gridEl) return;

          if (typeof Sortable === 'undefined') {
            console.error('Sortable not loaded');
            return;
          }

          this.sortable = new Sortable(gridEl, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'opacity-50',
            onEnd: () => this.updatePositions()
          });
        },

        destroySortable() {
          if (this.sortable) {
            this.sortable.destroy();
            this.sortable = null;
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
              this.destroySortable();
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
            
            widgetEl.innerHTML = data.success ? data.content : `<div class="p-4 text-red-400">${data.message}</div>`;
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
          return meta ? meta.getAttribute('content') : '';
        },

        getWidthClass(width) {
          return {
            'small': 'col-span-1',
            'medium': 'col-span-1 md:col-span-2',
            'large': 'col-span-1 md:col-span-2 lg:col-span-3'
          }[width] || 'col-span-1 md:col-span-2';
        }
      };
    });
  }
});