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
});
