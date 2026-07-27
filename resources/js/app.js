import './bootstrap';
import 'bootstrap';
import 'admin-lte/dist/js/adminlte';
import $ from 'jquery';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';

window.$ = window.jQuery = $;
window.Swal = Swal;
window.Chart = Chart;

DataTable(window, $);

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-datatable]').forEach((table) => {
        new DataTable(table, {
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            autoWidth: false,
        });
    });

    const alertMessage = document.querySelector('[data-toast-message]');

    if (alertMessage) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false,
            icon: alertMessage.dataset.toastType ?? 'success',
            title: alertMessage.dataset.toastMessage,
        });
    }

    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();

            Swal.fire({
                title: form.dataset.confirmTitle ?? 'Are you sure?',
                text: form.dataset.confirmText ?? 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: form.dataset.confirmButton ?? 'Yes, continue',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        });
    });

    const sidebarSearch = document.querySelector('[data-sidebar-search]');

    if (sidebarSearch) {
        sidebarSearch.addEventListener('input', () => {
            const term = sidebarSearch.value.trim().toLowerCase();

            document.querySelectorAll('.tracker-sidebar-nav .nav-item').forEach((item) => {
                const text = item.textContent.toLowerCase();
                item.style.display = term === '' || text.includes(term) ? '' : 'none';
            });

            document.querySelectorAll('.tracker-sidebar-nav .nav-header').forEach((header) => {
                let sibling = header.nextElementSibling;
                let hasVisible = false;

                while (sibling && !sibling.classList.contains('nav-header')) {
                    if (sibling.style.display !== 'none') {
                        hasVisible = true;
                    }
                    sibling = sibling.nextElementSibling;
                }

                header.style.display = hasVisible ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('[data-country-select]').forEach((countrySelect) => {
        const stateSelect = document.querySelector(countrySelect.dataset.stateTarget ?? '[data-state-select]');
        const citySelect = document.querySelector(countrySelect.dataset.cityTarget ?? '[data-city-select]');

        const loadStates = (countryId, selectedStateId) => {
            if (! stateSelect) {
                return;
            }

            stateSelect.innerHTML = '<option value="">Select State</option>';

            if (citySelect) {
                citySelect.innerHTML = '<option value="">Select City</option>';
            }

            if (! countryId) {
                return;
            }

            fetch(`/admin/geo/states/${countryId}`, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((states) => {
                    states.forEach((state) => {
                        const option = document.createElement('option');
                        option.value = state.id;
                        option.textContent = state.name;
                        option.selected = String(state.id) === String(selectedStateId);
                        stateSelect.appendChild(option);
                    });
                });
        };

        const loadCities = (stateId, selectedCityId) => {
            if (! citySelect) {
                return;
            }

            citySelect.innerHTML = '<option value="">Select City</option>';

            if (! stateId) {
                return;
            }

            fetch(`/admin/geo/cities/${stateId}`, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((cities) => {
                    cities.forEach((city) => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        option.selected = String(city.id) === String(selectedCityId);
                        citySelect.appendChild(option);
                    });
                });
        };

        countrySelect.addEventListener('change', () => loadStates(countrySelect.value));

        if (stateSelect) {
            stateSelect.addEventListener('change', () => loadCities(stateSelect.value));
        }
    });
});
