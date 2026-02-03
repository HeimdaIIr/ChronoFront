@extends('chronofront.timing-layout')

@section('title', 'Chronométrage')

@section('styles')
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #1a1d2e;
    color: #e4e4e7;
    overflow: hidden;
}

.chrono-container {
    display: flex;
    height: 100vh;
}

/* Sidebar */
.chrono-sidebar {
    width: 70px;
    background: #0f1117;
    border-right: 1px solid #2a2d3e;
    display: flex;
    flex-direction: column;
    padding: 1.5rem 0;
    gap: 0.5rem;
    transition: width 0.3s ease;
    overflow: hidden;
}

.chrono-sidebar.expanded {
    width: 250px;
}

.sidebar-toggle-btn {
    width: 70px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
    font-size: 1.3rem;
    cursor: pointer;
    transition: all 0.2s;
    background: transparent;
    border: none;
    margin-bottom: 1rem;
}

.sidebar-toggle-btn:hover {
    color: #e4e4e7;
    background: #1a1d2e;
}

.sidebar-icon {
    min-width: 70px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 0;
    color: #71717a;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    position: relative;
}

.sidebar-icon i {
    width: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sidebar-icon-label {
    opacity: 0;
    white-space: nowrap;
    font-size: 0.95rem;
    font-weight: 500;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.chrono-sidebar.expanded .sidebar-icon-label {
    opacity: 1;
}

.sidebar-icon:hover {
    color: #e4e4e7;
    background: #1a1d2e;
}

.sidebar-icon.active {
    color: #22c55e;
    background: #1a1d2e;
}

/* Main */
.chrono-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Top Bar */
.chrono-topbar {
    height: 70px;
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
    padding: 0 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.event-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-right: 1.5rem;
}

.event-status {
    padding: 0.5rem 1rem;
    background: #22c55e;
    color: white;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #22c55e;
    font-weight: 500;
}

.icon-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: #71717a;
    font-size: 1.3rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
}

.icon-btn:hover {
    background: #2a2d3e;
    color: #e4e4e7;
}

/* Content */
.chrono-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    height: calc(100vh - 70px);
    transition: grid-template-columns 0.3s ease;
}

.chrono-content.panel-closed {
    grid-template-columns: 1fr;
}

/* Left Side */
.chrono-left {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Clock + Status */
.clock-status-section {
    background: #0f1117;
    border-bottom: 1px solid #2a2d3e;
}

.main-clock {
    text-align: center;
    padding: 2rem 0 1.5rem;
    font-size: 8rem;
    font-weight: 200;
    letter-spacing: -0.04em;
    font-variant-numeric: tabular-nums;
}

.readers-status {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1rem 0 1.5rem;
}

.reader-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.reader-item span {
    color: #a1a1aa;
}

.reader-item strong {
    color: #22c55e;
}

.reader-item.warning strong {
    color: #f59e0b;
}

/* Filters */
.filters-bar {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: #1a1d2e;
    border-bottom: 1px solid #2a2d3e;
}

.search-box {
    flex: 1;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #71717a;
}

.search-box input {
    width: 100%;
    height: 45px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem 0 3rem;
    color: #e4e4e7;
    font-size: 0.95rem;
}

.search-box input:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-select {
    min-width: 140px;
    height: 45px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 0.9rem;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-filter {
    height: 45px;
    padding: 0 1.5rem;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-manual-time {
    height: 45px;
    padding: 0 1.5rem;
    background: #f59e0b;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-manual-time.has-times {
    background: #dc2626;
    animation: pulse 2s infinite;
}

.btn-manual-time:hover {
    transform: scale(1.05);
}

.btn-import-csv {
    height: 45px;
    padding: 0 1.5rem;
    background: #10b981;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes greenFlash {
    0% {
        box-shadow: inset 0 0 0 0 rgba(16, 185, 129, 0);
        border: 2px solid transparent;
    }
    50% {
        box-shadow: inset 0 0 20px rgba(16, 185, 129, 0.4);
        border: 2px solid #10b981;
    }
    100% {
        box-shadow: inset 0 0 0 0 rgba(16, 185, 129, 0);
        border: 2px solid transparent;
    }
}

.detection-flash {
    animation: greenFlash 0.8s ease-out;
}

/* Alert Badge */
.alert-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.alert-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.alert-badge.no-alerts {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

/* Alert section in runner panel */
.runner-alerts {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.runner-alerts h4 {
    margin: 0 0 0.75rem 0;
    color: #ef4444;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.alert-detail {
    background: rgba(0, 0, 0, 0.2);
    padding: 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
}

.alert-detail-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.alert-detail-text {
    color: #d1d5db;
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.alert-actions {
    display: flex;
    gap: 0.5rem;
}

.alert-btn {
    flex: 1;
    padding: 0.4rem 0.8rem;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.alert-btn-verify {
    background: #22c55e;
    color: white;
}

.alert-btn-verify:hover {
    background: #16a34a;
}

.alert-btn-ignore {
    background: #71717a;
    color: white;
}

.alert-btn-ignore:hover {
    background: #52525b;
}

/* Table */
.table-wrapper {
    flex: 1;
    overflow: auto;
}

.chrono-table {
    width: 100%;
    border-collapse: collapse;
}

.chrono-table thead {
    position: sticky;
    top: 0;
    background: #0f1117;
    z-index: 10;
}

.chrono-table thead th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #2a2d3e;
}

.chrono-table tbody tr {
    border-bottom: 1px solid #2a2d3e;
    cursor: pointer;
    transition: background 0.1s;
}

.chrono-table tbody tr:hover {
    background: #252836;
}

.chrono-table tbody tr.selected {
    background: #1e3a5f;
}

.chrono-table tbody tr.has-alert-duplicate {
    border-left: 3px solid #f59e0b;
    background: rgba(245, 158, 11, 0.05);
}

.chrono-table tbody tr.has-alert-speed {
    border-left: 3px solid #ef4444;
    background: rgba(239, 68, 68, 0.05);
}

.chrono-table tbody tr.has-alert-negative-time {
    border-left: 3px solid #dc2626;
    background: rgba(220, 38, 38, 0.08);
}

.chrono-table tbody td {
    padding: 1.1rem 1.5rem;
    font-size: 0.9rem;
}

.chrono-table tbody td strong {
    font-weight: 700;
    font-size: 1rem;
}

.cat-tag {
    padding: 0.25rem 0.6rem;
    background: #2a2d3e;
    border-radius: 5px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Right Panel */
.chrono-right {
    background: #0f1117;
    border-left: 2px solid #2a2d3e;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.detail-header {
    padding: 2rem;
    border-bottom: 1px solid #2a2d3e;
}

.bib-title {
    font-size: 0.75rem;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.bib-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.runner-name {
    font-size: 1.4rem;
    font-weight: 600;
}

.detail-body {
    padding: 2rem;
}

/* Timeline */
.timeline {
    margin-bottom: 2.5rem;
}

.timeline-item {
    display: flex;
    gap: 1.5rem;
    padding: 1rem 0;
    position: relative;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 40px;
    bottom: -15px;
    width: 2px;
    background: #2a2d3e;
}

.timeline-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
    z-index: 1;
}

.timeline-item.warning .timeline-dot {
    background: #f59e0b;
}

.timeline-content {
    flex: 1;
}

.checkpoint-label {
    font-size: 0.9rem;
    color: #a1a1aa;
    margin-bottom: 0.25rem;
}

.checkpoint-time {
    font-size: 1.3rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.checkpoint-action {
    font-size: 0.9rem;
    color: #f59e0b;
    text-decoration: underline;
    cursor: pointer;
}

/* Add Manual Time */
.manual-entry {
    background: #1a1d2e;
    border: 1px solid #2a2d3e;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.manual-entry h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.manual-entry input {
    width: 100%;
    height: 48px;
    background: #0f1117;
    border: 2px solid #2a2d3e;
    border-radius: 8px;
    padding: 0 1rem;
    color: #e4e4e7;
    font-size: 0.95rem;
    margin-bottom: 1rem;
}

.manual-entry input:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-manual {
    width: 100%;
    height: 48px;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-manual:hover {
    background: #2563eb;
}

/* Alert */
.alert-bar {
    position: fixed;
    bottom: 0;
    left: 70px;
    right: 0;
    padding: 1.25rem 2rem;
    background: #92400e;
    border-top: 1px solid #b45309;
    z-index: 100;
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #fef3c7;
}

/* Toast */
.toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    background: #22c55e;
    color: white;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 1000;
    animation: slideInRight 0.3s ease;
}

.toast.error {
    background: #ef4444;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Empty State */
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #71717a;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* Loading */
.loading {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 3px solid #2a2d3e;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Top Depart Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}

.modal-content {
    background: #1a1d2e;
    border: 2px solid #2a2d3e;
    border-radius: 16px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
}

.modal-content h3 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.race-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.race-btn {
    width: 100%;
    padding: 1rem 1.5rem;
    background: #10B981;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
}

.race-btn:hover:not(:disabled) {
    background: #059669;
}

.race-btn:disabled {
    background: #2a2d3e;
    color: #71717a;
    cursor: not-allowed;
}

.race-btn .time {
    font-size: 0.85rem;
    opacity: 0.9;
}

.btn-close-modal {
    width: 100%;
    padding: 0.75rem;
    background: #2a2d3e;
    border: none;
    border-radius: 8px;
    color: #e4e4e7;
    font-weight: 500;
    cursor: pointer;
}

/* Responsive pour écrans ≤ 1920px (laptops 15" Full HD) */
@media (max-width: 1920px) {
    .chrono-sidebar {
        width: 50px;
    }

    .chrono-sidebar.expanded {
        width: 220px;
    }

    .sidebar-toggle-btn {
        width: 50px;
        height: 36px;
        font-size: 1.1rem;
    }

    .sidebar-icon {
        min-width: 50px;
        height: 36px;
        font-size: 1.1rem;
    }

    .sidebar-icon i {
        width: 50px;
    }

    .chrono-topbar {
        height: 45px;
        padding: 0 1.2rem;
    }

    .event-title {
        font-size: 0.95rem;
    }

    .event-status {
        padding: 0.35rem 0.65rem;
        font-size: 0.7rem;
    }

    .topbar-right {
        gap: 0.8rem;
    }

    .icon-btn {
        width: 28px;
        height: 28px;
        font-size: 0.9rem;
    }

    .chrono-content {
        grid-template-columns: 1fr 280px;
        height: calc(100vh - 45px);
    }

    .chrono-content.panel-closed {
        grid-template-columns: 1fr;
    }

    .main-clock {
        font-size: 3.6rem;
        padding: 1rem 0 0.7rem;
    }

    .readers-status {
        gap: 1.2rem;
        padding: 0.5rem 0 0.7rem;
        font-size: 0.75rem;
    }

    .filters-bar {
        padding: 0.7rem 1.2rem;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .search-box {
        min-width: 150px;
        flex: 1;
    }

    .search-box input,
    .filter-select,
    .btn-filter,
    .btn-manual-time,
    .btn-import-csv {
        height: 30px;
        font-size: 0.7rem;
    }

    .btn-filter,
    .btn-manual-time,
    .btn-import-csv {
        padding: 0 0.9rem;
    }

    .filter-select {
        min-width: 95px;
        padding: 0 0.6rem;
    }

    .chrono-table thead th {
        padding: 0.55rem 0.7rem;
        font-size: 0.6rem;
    }

    .chrono-table tbody td {
        padding: 0.6rem 0.7rem;
        font-size: 0.7rem;
    }

    .chrono-table tbody td strong {
        font-size: 0.75rem;
    }

    .cat-tag {
        padding: 0.15rem 0.4rem;
        font-size: 0.6rem;
    }

    .detail-header,
    .detail-body {
        padding: 1.1rem;
    }

    .bib-value {
        font-size: 1.6rem;
    }

    .runner-name {
        font-size: 1rem;
    }

    .checkpoint-time {
        font-size: 0.95rem;
    }

    .timeline-dot {
        width: 18px;
        height: 18px;
    }

    .alert-bar {
        left: 50px;
        padding: 0.7rem 1.2rem;
    }
}

/* Responsive pour écrans ≤ 1440px (laptops standards) */
@media (max-width: 1440px) {
    .chrono-sidebar {
        width: 60px;
    }

    .chrono-sidebar.expanded {
        width: 230px;
    }

    .sidebar-toggle-btn {
        width: 60px;
        height: 45px;
        font-size: 1.2rem;
    }

    .sidebar-icon {
        min-width: 60px;
        height: 45px;
        font-size: 1.3rem;
    }

    .sidebar-icon i {
        width: 60px;
    }

    .chrono-topbar {
        height: 55px;
        padding: 0 1.5rem;
    }

    .event-title {
        font-size: 1.2rem;
    }

    .chrono-content {
        grid-template-columns: 1fr 340px;
        height: calc(100vh - 55px);
    }

    .chrono-content.panel-closed {
        grid-template-columns: 1fr;
    }

    .main-clock {
        font-size: 5.5rem;
        padding: 1.5rem 0 1rem;
    }

    .filters-bar {
        padding: 1rem 1.5rem;
        gap: 0.75rem;
    }

    .search-box input,
    .filter-select,
    .btn-filter,
    .btn-manual-time,
    .btn-import-csv {
        height: 38px;
        font-size: 0.85rem;
    }

    .filter-select {
        min-width: 120px;
        padding: 0 0.75rem;
    }

    .chrono-table thead th {
        padding: 0.75rem 1rem;
        font-size: 0.7rem;
    }

    .chrono-table tbody td {
        padding: 0.9rem 1rem;
        font-size: 0.85rem;
    }

    .detail-header,
    .detail-body {
        padding: 1.5rem;
    }

    .bib-value {
        font-size: 2rem;
    }

    .runner-name {
        font-size: 1.2rem;
    }
}

/* Responsive pour écrans ≤ 1280px (petits laptops) */
@media (max-width: 1280px) {
    .chrono-sidebar {
        width: 55px;
    }

    .chrono-sidebar.expanded {
        width: 210px;
    }

    .sidebar-toggle-btn {
        width: 55px;
        height: 40px;
        font-size: 1.1rem;
    }

    .sidebar-icon {
        min-width: 55px;
        height: 40px;
        font-size: 1.2rem;
    }

    .sidebar-icon i {
        width: 55px;
    }

    .chrono-topbar {
        height: 50px;
        padding: 0 1rem;
        gap: 0.75rem;
    }

    .event-title {
        font-size: 1.1rem;
        margin-right: 1rem;
    }

    .topbar-right {
        gap: 1rem;
    }

    .icon-btn {
        width: 35px;
        height: 35px;
        font-size: 1.1rem;
    }

    .chrono-content {
        grid-template-columns: 1fr 300px;
        height: calc(100vh - 50px);
    }

    .chrono-content.panel-closed {
        grid-template-columns: 1fr;
    }

    .main-clock {
        font-size: 4.5rem;
        padding: 1rem 0 0.75rem;
    }

    .readers-status {
        gap: 1.5rem;
        padding: 0.75rem 0 1rem;
        font-size: 0.85rem;
    }

    .filters-bar {
        padding: 0.85rem 1rem;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .search-box {
        min-width: 200px;
    }

    .search-box input,
    .filter-select,
    .btn-filter,
    .btn-manual-time,
    .btn-import-csv {
        height: 36px;
        font-size: 0.8rem;
    }

    .btn-filter,
    .btn-manual-time,
    .btn-import-csv {
        padding: 0 1rem;
    }

    .filter-select {
        min-width: 100px;
        padding: 0 0.6rem;
    }

    .chrono-table thead th {
        padding: 0.6rem 0.85rem;
        font-size: 0.65rem;
    }

    .chrono-table tbody td {
        padding: 0.75rem 0.85rem;
        font-size: 0.8rem;
    }

    .cat-tag {
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
    }

    .detail-header,
    .detail-body {
        padding: 1.25rem;
    }

    .bib-value {
        font-size: 1.75rem;
    }

    .runner-name {
        font-size: 1.1rem;
    }

    .checkpoint-time {
        font-size: 1.15rem;
    }

    .alert-bar {
        left: 55px;
        padding: 1rem 1.5rem;
    }
}
</style>
@endsection

@section('content')
@php
    // Vérifier si au moins un lecteur a un mode avec vagues
    $hasWavesMode = \App\Models\Reader::whereIn('mode', ['single_reader_waves', 'multi_reader_waves'])->exists();
@endphp
<div class="chrono-container" x-data="chronoApp()">
    <!-- Sidebar -->
    <div class="chrono-sidebar" x-data="{ sidebarExpanded: false }" :class="{ 'expanded': sidebarExpanded }">
        <!-- Toggle Button -->
        <button class="sidebar-toggle-btn" @click="sidebarExpanded = !sidebarExpanded" title="Ouvrir/Fermer le menu">
            <i class="bi" :class="sidebarExpanded ? 'bi-chevron-left' : 'bi-chevron-right'"></i>
        </button>

        <!-- Navigation -->
        <a href="{{ route('dashboard') }}" class="sidebar-icon" title="Tableau de bord">
            <i class="bi bi-house-door"></i>
            <span class="sidebar-icon-label">Tableau de bord</span>
        </a>
        <a href="{{ route('events') }}" class="sidebar-icon" title="Événements">
            <i class="bi bi-calendar-event"></i>
            <span class="sidebar-icon-label">Événements</span>
        </a>
        <a href="{{ route('entrants') }}" class="sidebar-icon" title="Participants">
            <i class="bi bi-people"></i>
            <span class="sidebar-icon-label">Participants</span>
        </a>
        <a href="{{ route('races') }}" class="sidebar-icon" title="Parcours">
            <i class="bi bi-trophy"></i>
            <span class="sidebar-icon-label">Parcours</span>
        </a>
        @if($hasWavesMode)
        <a href="{{ route('waves') }}" class="sidebar-icon" title="Vagues">
            <i class="bi bi-water"></i>
            <span class="sidebar-icon-label">Vagues</span>
        </a>
        @endif
        <a href="{{ route('timing') }}" class="sidebar-icon active" title="Chronométrage">
            <i class="bi bi-stopwatch"></i>
            <span class="sidebar-icon-label">Chronométrage</span>
        </a>
        <a href="{{ route('results') }}" class="sidebar-icon" title="Résultats">
            <i class="bi bi-bar-chart"></i>
            <span class="sidebar-icon-label">Résultats</span>
        </a>
    </div>

    <!-- Main -->
    <div class="chrono-main">
        <!-- Top Bar -->
        <div class="chrono-topbar">
            <div style="display: flex; align-items: center;">
                <span class="event-title" x-text="eventName || 'ChronoFront'"></span>
                <span class="event-status" x-show="hasOngoingRaces()" style="background: #10B981;">Course en cours</span>
                <span class="event-status" x-show="!hasOngoingRaces() && races.length > 0" style="background: #f59e0b;">En attente</span>
            </div>
            <div class="topbar-right">
                <!-- Action buttons -->
                <button class="btn-filter" @click="openTopDepartModal()" style="height: 38px; background: #10B981;">
                    <i class="bi bi-flag-fill"></i>
                    TOP DÉPART
                </button>
                <div style="position: relative; display: inline-block;">
                    <button class="btn-manual-time" @click="addManualTimestamp" :class="{ 'has-times': manualTimestamps.length > 0 }" style="height: 38px;">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span x-show="manualTimestamps.length === 0">TEMPS MANUEL</span>
                        <span x-show="manualTimestamps.length > 0" x-text="manualTimestamps.length"></span>
                    </button>
                    <button x-show="manualTimestamps.length > 0"
                            @click.stop="quickClearManualTimestamps()"
                            title="Effacer tous les temps"
                            style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border-radius: 50%; background: #ef4444; color: white; border: 2px solid #18181b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; padding: 0;">
                        ×
                    </button>
                </div>
                <button class="btn-filter" @click="showRfidFileModal = true" style="background: #8b5cf6; height: 38px;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    IMPORTER HEURES
                </button>
                <button class="btn-filter" @click="confirmClearAllArrivals()" style="background: #ef4444; height: 38px;" title="Supprimer toutes les heures d'arrivée">
                    <i class="bi bi-trash-fill"></i>
                    SUPPRIMER ARRIVÉES
                </button>
                <div class="alert-badge" :class="{ 'no-alerts': getPendingAlertsCount() === 0 }" style="margin-left: 1rem;">
                    <i class="bi bi-bell-fill"></i>
                    <span x-text="getPendingAlertsCount() + ' alerte' + (getPendingAlertsCount() > 1 ? 's' : '')"></span>
                </div>
                <div style="font-size: 1.1rem; font-variant-numeric: tabular-nums; color: #a1a1aa; margin-left: 1rem;" x-text="currentTime"></div>
                <a href="{{ route('dashboard') }}" class="icon-btn"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>

        <!-- Content -->
        <div class="chrono-content" :class="{ 'panel-closed': !selectedResult }">
            <!-- Left -->
            <div class="chrono-left">
                <!-- Clock + Readers Status -->
                <div class="clock-status-section" :class="{ 'detection-flash': detectionFlash }">
                    <!-- Race Selector -->
                    <div style="text-align: center; padding-top: 1.5rem; padding-bottom: 0.5rem;">
                        <select x-model="selectedRaceId" @change="switchRaceChrono()"
                                style="background: #1a1d2e; color: #e4e4e7; border: 1px solid #2a2d3e; border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.95rem; cursor: pointer;">
                            <option value="">Sélectionner un parcours</option>
                            <template x-for="race in races" :key="race.id">
                                <option :value="race.id" x-text="race.name + (race.start_time ? ' (Démarré)' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Waves Chrono Display (Multi-clocks) -->
                    <div x-show="selectedRaceId && getFilteredWaves().length > 0" style="padding: 1rem 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                            <template x-for="wave in getFilteredWaves()" :key="wave.id">
                                <div style="text-align: center; background: #1a1d2e; border-radius: 12px; padding: 1.5rem; border: 2px solid #2a2d3e;" :style="wave.real_start_time ? 'border-color: #22c55e;' : ''">
                                    <!-- Wave Name -->
                                    <div style="font-size: 0.9rem; font-weight: 600; color: #a1a1aa; margin-bottom: 0.5rem;" x-text="wave.name"></div>
                                    <!-- Clock -->
                                    <div style="font-size: 3rem; font-weight: 200; letter-spacing: -0.04em; font-variant-numeric: tabular-nums;" :style="wave.real_start_time ? 'color: #e4e4e7;' : 'color: #71717a;'" x-text="getWaveChrono(wave)"></div>
                                    <!-- Start Time or Status -->
                                    <div style="font-size: 0.85rem; color: #a1a1aa; margin-top: 0.5rem;" x-show="wave.real_start_time">
                                        <span>Départ: </span>
                                        <span x-text="formatTime(wave.real_start_time)"></span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #f59e0b; margin-top: 0.5rem;" x-show="!wave.real_start_time">
                                        En attente
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Fallback: Race Chrono Display (no waves) -->
                    <div x-show="selectedRaceId && getFilteredWaves().length === 0">
                        <div class="main-clock" x-text="raceChrono" x-show="getSelectedRace()?.start_time"></div>
                        <div class="main-clock" style="font-size: 3rem; color: #71717a;" x-show="!getSelectedRace()?.start_time">
                            -- : -- : --
                        </div>
                        <div style="text-align: center; padding-bottom: 0.5rem; color: #a1a1aa; font-size: 0.9rem;" x-show="getSelectedRace()?.start_time">
                            <span>Départ: </span>
                            <span x-text="formatTime(getSelectedRace()?.start_time)"></span>
                        </div>
                    </div>

                    <!-- No race selected -->
                    <div class="main-clock" style="font-size: 3rem; color: #71717a;" x-show="!selectedRaceId">
                        -- : -- : --
                    </div>

                    <div class="readers-status" x-show="readers.length > 0">
                        <template x-for="reader in readers" :key="reader.id">
                            <div class="reader-item" :class="{ 'warning': !reader.is_online }">
                                <span x-text="reader.location || reader.name"></span>:
                                <strong x-text="reader.is_online ? 'OK' : 'Hors ligne'"></strong>
                            </div>
                        </template>
                    </div>
                    <div x-show="readers.length === 0" class="text-center py-3 text-muted" style="font-size: 0.9rem;">
                        Aucun lecteur configuré
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-bar" style="flex-wrap: wrap; gap: 0.75rem;">
                    <!-- Row 1: Search and core filters -->
                    <div class="search-box" style="flex: 1; min-width: 250px;">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Rechercher dossard / nom" x-model="searchQuery" @input="filterResults">
                    </div>
                    <select class="filter-select" x-model="raceFilter" @change="filterResults" style="min-width: 150px;">
                        <option value="">Tous parcours</option>
                        <template x-for="race in [...new Set(results.map(r => r.race?.name).filter(r => r))]" :key="race">
                            <option :value="race" x-text="race"></option>
                        </template>
                    </select>
                    <select class="filter-select" x-model="categoryFilter" @change="filterResults" style="min-width: 150px;">
                        <option value="">Toutes catégories</option>
                        <template x-for="category in [...new Set(results.map(r => r.entrant?.category?.name).filter(c => c))]" :key="category">
                            <option :value="category" x-text="category"></option>
                        </template>
                    </select>
                    <select class="filter-select" x-model="sasFilter" @change="filterResults" style="min-width: 120px;">
                        <option value="">Tous SAS</option>
                        <template x-for="wave in [...new Set(results.map(r => r.wave?.name).filter(w => w))]" :key="wave">
                            <option :value="wave" x-text="wave"></option>
                        </template>
                    </select>
                    <select class="filter-select" x-model="checkpointFilter" @change="filterResults" style="min-width: 130px;">
                        <option value="">Tous checkpoints</option>
                        <template x-for="checkpoint in [...new Set(results.map(r => r.reader_location).filter(c => c))]" :key="checkpoint">
                            <option :value="checkpoint" x-text="checkpoint"></option>
                        </template>
                    </select>
                    <select class="filter-select" x-model="lapFilter" @change="filterResults" style="min-width: 100px;">
                        <option value="">Tous tours</option>
                        <template x-for="lap in [...new Set(results.map(r => r.lap_number).filter(l => l))].sort((a,b) => a - b)" :key="lap">
                            <option :value="lap" x-text="'Tour ' + lap"></option>
                        </template>
                    </select>
                    <select class="filter-select" x-model="sortBy" @change="sortResults" style="border-left: 2px solid #3b82f6; min-width: 140px;">
                        <option value="recent">Tri: Plus récent</option>
                        <option value="position">Tri: Position</option>
                        <option value="time">Tri: Temps</option>
                    </select>
                    <button class="btn-import-csv" @click="showManualTimesModal = true" x-show="manualTimestamps.length > 0">
                        <i class="bi bi-file-earmark-arrow-up-fill"></i>
                        ATTRIBUER
                    </button>
                </div>

                <!-- Table -->
                <div class="table-wrapper" x-show="!loading">
                    <table class="chrono-table">
                        <thead>
                            <tr>
                                <th>Pos</th>
                                <th>Cat</th>
                                <th>Dossard</th>
                                <th>Nom</th>
                                <th>Catégorie</th>
                                <th>Parcours</th>
                                <th>SAS</th>
                                <th>Lecteur</th>
                                <th>Tour</th>
                                <th>Temps</th>
                                <th>Vit</th>
                                <th>Détection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="result in displayedResults" :key="result.id">
                                <tr :class="{ 'selected': selectedResult?.id === result.id, [getResultAlertClass(result)]: true }" @click="selectResult(result)">
                                    <td><strong x-text="result.position || '-'"></strong></td>
                                    <td><strong x-text="result.category_position || '-'"></strong></td>
                                    <td><strong x-text="result.entrant?.bib_number"></strong></td>
                                    <td x-text="(result.entrant?.firstname || '') + ' ' + (result.entrant?.lastname || '')"></td>
                                    <td><span class="cat-tag" x-text="result.entrant?.category?.name || '-'"></span></td>
                                    <td x-text="result.race?.name || '-'"></td>
                                    <td x-text="result.wave?.name || '-'"></td>
                                    <td x-text="result.reader_location || '-'"></td>
                                    <td><strong x-text="result.lap_number || '-'"></strong></td>
                                    <td><strong x-text="getDisplayTime(result)"></strong></td>
                                    <td x-text="getLapSpeed(result)"></td>
                                    <td x-text="formatTime(result.raw_time)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="displayedResults.length === 0" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Aucune détection</p>
                    </div>
                </div>

                <div x-show="loading" class="loading">
                    <div class="spinner"></div>
                    <p style="color: #71717a;">Chargement...</p>
                </div>
            </div>

            <!-- Right -->
            <div class="chrono-right" x-show="selectedResult">
                <div class="detail-header" style="position: relative;">
                    <!-- Delete Button -->
                    <button @click="deleteDetection(selectedResult)"
                            style="position: absolute; top: 1rem; right: 3.5rem; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #ef4444; border: none; border-radius: 6px; color: white; cursor: pointer; transition: all 0.2s; font-size: 1rem;"
                            onmouseover="this.style.background='#dc2626';"
                            onmouseout="this.style.background='#ef4444';"
                            title="Supprimer cette détection">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                    <!-- Close Button -->
                    <button @click="selectedResult = null"
                            style="position: absolute; top: 1rem; right: 1rem; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #2a2d3e; border: none; border-radius: 6px; color: #a1a1aa; cursor: pointer; transition: all 0.2s; font-size: 1.2rem;"
                            onmouseover="this.style.background='#3b3e4e'; this.style.color='#e4e4e7';"
                            onmouseout="this.style.background='#2a2d3e'; this.style.color='#a1a1aa';"
                            title="Fermer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="bib-title">Dossard</div>
                    <!-- Editable Bib Number -->
                    <div class="bib-value"
                         x-show="editingField !== 'bib_number'"
                         @dblclick="startEditField('bib_number', selectedResult?.entrant?.bib_number)"
                         style="cursor: pointer;"
                         x-text="'#' + (selectedResult?.entrant?.bib_number || '')">
                    </div>
                    <input x-show="editingField === 'bib_number'"
                           x-model="editingValue"
                           @keyup.enter="saveEditField()"
                           @keyup.escape="cancelEditField()"
                           @blur="saveEditField()"
                           type="text"
                           class="form-control"
                           style="background: #1a1d2e; color: white; border: 1px solid #3b82f6; text-align: center; font-size: 1.5rem; font-weight: 700;">

                    <!-- Editable Runner Name -->
                    <div class="runner-name"
                         x-show="editingField !== 'firstname' && editingField !== 'lastname'"
                         @dblclick="startEditField('firstname', selectedResult?.entrant?.firstname)"
                         style="cursor: pointer;"
                         x-text="(selectedResult?.entrant?.firstname || '') + ' ' + (selectedResult?.entrant?.lastname || '')">
                    </div>
                    <div x-show="editingField === 'firstname' || editingField === 'lastname'" style="display: flex; gap: 0.5rem;">
                        <input x-show="editingField === 'firstname'"
                               x-model="editingValue"
                               @keyup.enter="saveEditField()"
                               @keyup.escape="cancelEditField()"
                               @blur="saveEditField()"
                               type="text"
                               placeholder="Prénom"
                               class="form-control"
                               style="background: #1a1d2e; color: white; border: 1px solid #3b82f6;">
                        <button x-show="editingField === 'firstname'"
                                @click="editingField = 'lastname'; editingValue = selectedResult?.entrant?.lastname || ''"
                                class="btn btn-sm btn-secondary">
                            Nom →
                        </button>
                        <input x-show="editingField === 'lastname'"
                               x-model="editingValue"
                               @keyup.enter="saveEditField()"
                               @keyup.escape="cancelEditField()"
                               @blur="saveEditField()"
                               type="text"
                               placeholder="Nom"
                               class="form-control"
                               style="background: #1a1d2e; color: white; border: 1px solid #3b82f6;">
                    </div>
                </div>

                <div class="detail-body">
                    <!-- Alerts Section -->
                    <div x-show="selectedResult?.alerts && selectedResult.alerts.filter(a => a.status === 'pending').length > 0" class="runner-alerts">
                        <h4>
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span x-text="(selectedResult?.alerts?.filter(a => a.status === 'pending').length || 0) + ' alerte' + ((selectedResult?.alerts?.filter(a => a.status === 'pending').length || 0) > 1 ? 's' : '')"></span>
                        </h4>
                        <div class="alert-list">
                            <template x-for="alert in (selectedResult?.alerts || []).filter(a => a.status === 'pending')" :key="alert.id">
                                <div class="alert-detail">
                                    <div class="alert-detail-header">
                                        <span x-text="alert.icon"></span>
                                        <span x-text="alert.title"></span>
                                    </div>
                                    <div class="alert-detail-text" x-text="alert.details"></div>
                                    <div class="alert-actions">
                                        <button class="alert-btn alert-btn-verify" @click="markAlertAsVerified(selectedResult, alert.id)">
                                            <i class="bi bi-check-circle-fill"></i> Vérifier
                                        </button>
                                        <button class="alert-btn alert-btn-ignore" @click="markAlertAsIgnored(selectedResult, alert.id)">
                                            <i class="bi bi-x-circle-fill"></i> Ignorer
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Info de base -->
                    <div class="mb-3">
                        <!-- Épreuve -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <div style="color: #a1a1aa; font-size: 0.85rem;">Épreuve:</div>
                            <div style="text-align: right; max-width: 65%;">
                                <!-- Editable Race -->
                                <div x-show="editingField !== 'race_id'"
                                     @dblclick="startEditField('race_id', selectedResult?.race?.id)"
                                     style="cursor: pointer; font-size: 0.9rem; font-weight: 500;"
                                     x-text="selectedResult?.race?.name || '-'">
                                </div>
                                <select x-show="editingField === 'race_id'"
                                        x-model="editingValue"
                                        @change="saveEditField()"
                                        @blur="cancelEditField()"
                                        class="form-select form-select-sm"
                                        style="background: #1a1d2e; color: white; border: 1px solid #3b82f6; width: 100%;">
                                    <template x-for="race in races" :key="race.id">
                                        <option :value="race.id" x-text="race.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <!-- Catégorie -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <div style="color: #a1a1aa; font-size: 0.85rem;">Catégorie:</div>
                            <div style="text-align: right; max-width: 65%;">
                                <!-- Editable Category -->
                                <div x-show="editingField !== 'category_id'"
                                     @dblclick="startEditField('category_id', selectedResult?.entrant?.category?.id)"
                                     style="cursor: pointer; font-weight: 500;"
                                     x-text="selectedResult?.entrant?.category?.name || '-'">
                                </div>
                                <select x-show="editingField === 'category_id'"
                                        x-model="editingValue"
                                        @change="saveEditField()"
                                        @blur="cancelEditField()"
                                        class="form-select form-select-sm"
                                        style="background: #1a1d2e; color: white; border: 1px solid #3b82f6; width: 100%;">
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Runner Status -->
                    <div class="mb-3" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="color: #a1a1aa; font-size: 0.85rem;">Statut:</div>
                            <div style="text-align: right; max-width: 65%;">
                                <select :value="getRunnerStatusValue(selectedResult)"
                                        @change="updateRunnerStatus(selectedResult, $event.target.value)"
                                        style="width: 100%; padding: 0.5rem; background: #1a1d2e; color: white; border: 1px solid #2a2d3e; border-radius: 6px; font-size: 0.9rem; font-weight: 500;">
                                    <option value="active">Actif</option>
                                    <option value="dns">Non partant</option>
                                    <option value="dnf">ABD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Checkpoint Timeline -->
                    <div class="mb-4" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; color: #e4e4e7;">
                            <i class="bi bi-geo-alt-fill"></i> Passages enregistrés
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <template x-for="(checkpoint, index) in runnerCheckpoints" :key="checkpoint.id || index">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem; background: #1a1d2e; border-radius: 8px; border-left: 3px solid; gap: 0.75rem;"
                                     :style="`border-left-color: ${checkpoint.is_estimated ? '#f59e0b' : '#22c55e'}`">
                                    <!-- Nom du checkpoint -->
                                    <div style="display: flex; align-items: center; gap: 0.5rem; max-width: 60%; min-width: 0; flex-shrink: 1;">
                                        <div style="flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%;"
                                             :style="`background: ${checkpoint.is_estimated ? '#f59e0b' : '#22c55e'}`"></div>
                                        <div style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                             x-text="checkpoint.location"></div>
                                    </div>
                                    <!-- Temps -->
                                    <div style="font-weight: 600; font-size: 0.95rem; white-space: nowrap; flex-shrink: 0; margin-left: auto;"
                                         :style="`color: ${checkpoint.is_estimated ? '#f59e0b' : '#22c55e'}`"
                                         x-text="checkpoint.time_display"></div>
                                    <!-- Boutons d'édition -->
                                    <div style="display: flex; gap: 0.25rem; flex-shrink: 0;" x-show="!checkpoint.is_estimated && checkpoint.id">
                                        <button @click="adjustResultTime(checkpoint.id, 5)"
                                                style="padding: 0.25rem 0.5rem; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer;"
                                                title="Ajouter 5 secondes">
                                            +5s
                                        </button>
                                        <button @click="adjustResultTime(checkpoint.id, -5)"
                                                style="padding: 0.25rem 0.5rem; background: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer;"
                                                title="Retirer 5 secondes">
                                            -5s
                                        </button>
                                        <button @click="deleteResult(checkpoint.id)"
                                                style="padding: 0.25rem 0.5rem; background: #ef4444; color: white; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer;"
                                                title="Supprimer ce passage">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="runnerCheckpoints.length === 0" style="text-align: center; padding: 1rem; color: #71717a; font-size: 0.85rem;">
                                Aucun passage enregistré
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="mb-4" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <div style="color: #22c55e; font-size: 0.85rem; font-weight: 600;">TEMPS TOTAL:</div>
                            <div style="text-align: right; font-size: 1.5rem; font-weight: 700; color: #22c55e;" x-text="selectedResult?.formatted_time || '-'"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;" x-show="runnerAverageSpeed">
                            <div style="color: #a1a1aa; font-size: 0.85rem;">Vitesse moyenne:</div>
                            <div style="text-align: right; font-weight: 500;" x-text="runnerAverageSpeed ? runnerAverageSpeed.toFixed(2) + ' km/h' : '-'"></div>
                        </div>
                    </div>

                    <!-- Add Intermediate Time -->
                    <div class="manual-entry" style="border-top: 1px solid #2a2d3e; padding-top: 1rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; color: #e4e4e7;">
                            <i class="bi bi-plus-circle-fill"></i> Ajouter temps intermédiaire
                        </h4>
                        <form @submit.prevent="addIntermediateTime" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <!-- Checkpoint -->
                            <div>
                                <label style="display: block; color: #a1a1aa; font-size: 0.8rem; margin-bottom: 0.5rem;">Checkpoint</label>
                                <select x-model="intermediateReaderId"
                                        style="width: 100%; padding: 0.65rem; background: #1a1d2e; color: white; border: 1px solid #2a2d3e; border-radius: 6px; font-size: 0.9rem;">
                                    <option value="">Sélectionner checkpoint</option>
                                    <template x-for="reader in readers" :key="reader.id">
                                        <option :value="reader.id" x-text="reader.location"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Date et Heure -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div style="min-width: 0;">
                                    <label style="display: block; color: #a1a1aa; font-size: 0.8rem; margin-bottom: 0.5rem;">Date</label>
                                    <input type="date"
                                           x-model="intermediateDate"
                                           style="width: 100%; min-width: 0; padding: 0.5rem; background: #1a1d2e; color: white; border: 1px solid #2a2d3e; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                                </div>
                                <div style="min-width: 0;">
                                    <label style="display: block; color: #a1a1aa; font-size: 0.8rem; margin-bottom: 0.5rem;">Heure</label>
                                    <input type="time"
                                           x-model="intermediateTime"
                                           step="1"
                                           style="width: 100%; min-width: 0; padding: 0.5rem; background: #1a1d2e; color: white; border: 1px solid #2a2d3e; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                                <button type="button"
                                        @click="setIntermediateTimeNow()"
                                        style="padding: 0.6rem; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500; white-space: nowrap;">
                                    <i class="bi bi-clock"></i> Maintenant
                                </button>
                                <button type="submit"
                                        :disabled="!intermediateReaderId || !intermediateDate || !intermediateTime || saving"
                                        style="padding: 0.6rem; background: #22c55e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500;"
                                        :style="(!intermediateReaderId || !intermediateDate || !intermediateTime || saving) ? 'opacity: 0.5; cursor: not-allowed;' : ''">
                                    <i class="bi bi-stopwatch"></i>
                                    <span x-show="!saving">Ajouter</span>
                                    <span x-show="saving">...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Bar - Only show if there are real issues -->
    <div class="alert-bar" x-show="alertMessage">
        <div class="alert-content">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
            <span x-text="alertMessage"></span>
            <button @click="alertMessage = null" style="background: none; border: none; color: #fef3c7; margin-left: auto; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toastMessage" class="toast" :class="toastType">
        <i class="bi" :class="toastType === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'"></i>
        <span x-text="toastMessage"></span>
    </div>

    <!-- Top Depart Modal -->
    <div x-show="showTopDepartModal" class="modal-overlay" @click.self="showTopDepartModal = false">
        <div class="modal-content" style="max-width: 1000px; max-height: 90vh; display: flex; flex-direction: column;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.3rem; color: #e4e4e7;">Gestion des départs</h3>
                <button @click="showTopDepartModal = false"
                        style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: transparent; border: 1px solid #2a2d3e; border-radius: 6px; color: #71717a; cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.background='#2a2d3e'; this.style.color='#e4e4e7';"
                        onmouseout="this.style.background='transparent'; this.style.color='#71717a';">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Table Container (scrollable) -->
            <div style="flex: 1; overflow-y: auto; border: 1px solid #2a2d3e; border-radius: 8px;">
                <!-- Empty State -->
                <div x-show="races.length === 0" style="padding: 3rem; text-align: center; color: #71717a;">
                    <i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0;">Aucun parcours trouvé</p>
                </div>

                <!-- Table -->
                <table x-show="races.length > 0" style="width: 100%; border-collapse: collapse; background: #0f1117; color: #e4e4e7;">
                    <thead style="position: sticky; top: 0; background: #1a1d2e; border-bottom: 2px solid #2a2d3e; z-index: 10;">
                        <tr>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: #a1a1aa; border-right: 1px solid #2a2d3e; width: 80px;">ID</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: #a1a1aa; border-right: 1px solid #2a2d3e;">Parcours</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; color: #a1a1aa; width: 350px;">Heure de départ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="race in races" :key="race.id">
                            <tr style="border-bottom: 1px solid #2a2d3e;">
                                <td style="padding: 1rem; border-right: 1px solid #2a2d3e; font-weight: 600; color: #e4e4e7;">
                                    <span x-text="race.id"></span>
                                </td>
                                <td style="padding: 1rem; border-right: 1px solid #2a2d3e; font-weight: 500; color: #e4e4e7;">
                                    <span x-text="race.name"></span>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; gap: 0.75rem; align-items: stretch;">
                                        <input type="text"
                                               :value="race.start_time ? formatTimeInput(race.start_time) : ''"
                                               @blur="updateRaceStartTime(race, $event.target.value)"
                                               placeholder="--/--/---- --:--:--"
                                               style="flex: 1; padding: 0.75rem 1rem; background: #1a1d2e; color: #e4e4e7; border: 1px solid #2a2d3e; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 1rem; height: 48px;"
                                               :style="race.start_time ? 'border-color: #22c55e;' : ''">
                                        <button @click="topDepart(race)"
                                                style="padding: 0.75rem 1.5rem; background: #10B981; color: white; border: none; border-radius: 0; cursor: pointer; font-weight: 600; white-space: nowrap; transition: all 0.2s; height: 48px;"
                                                onmouseover="this.style.background='#059669';"
                                                onmouseout="this.style.background='#10B981';">
                                            TOP
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Manual Times Import Modal -->
    <div x-show="showManualTimesModal" class="modal-overlay" @click.self="showManualTimesModal = false">
        <div class="modal-content" style="max-width: 500px; max-height: 90vh; display: flex; flex-direction: column;">
            <h3 style="margin: 0 0 0.75rem 0; font-size: 1.1rem;">Attribution des temps manuels</h3>

            <!-- Content (no scroll here) -->
            <div style="margin-bottom: 0.5rem;">
                <div style="margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: #dc2626; font-size: 0.9rem;">
                            <i class="bi bi-clock-history"></i>
                            <span x-text="manualTimestamps.length"></span> temps
                        </h4>
                        <div style="display: flex; gap: 0.35rem;">
                            <button @click="addManualTimestamp()" style="padding: 0.35rem 0.65rem; background: #22c55e; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 0.25rem;">
                                <i class="bi bi-plus-circle-fill"></i> Ajouter
                            </button>
                            <button @click="clearManualTimestamps()" style="padding: 0.35rem 0.65rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable list of timestamps (max 3 lines visible) -->
                    <div style="max-height: 120px; overflow-y: auto; background: #f9fafb; border-radius: 4px; padding: 0.5rem;">
                    <template x-for="(ts, index) in manualTimestamps" :key="index">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem; background: white; border-radius: 4px; margin-bottom: 0.35rem; font-size: 0.85rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                                <span style="font-weight: 600; color: #6b7280; font-size: 0.8rem;" x-text="`#${index + 1}`"></span>

                                <!-- Editable timestamp -->
                                <div style="flex: 1;">
                                    <span x-show="editingTimestampIndex !== index"
                                          @click="startEditingTimestamp(index)"
                                          style="cursor: pointer; padding: 0.2rem 0.4rem; border-radius: 3px; transition: background 0.2s; font-size: 0.85rem;"
                                          onmouseover="this.style.background='#f3f4f6'"
                                          onmouseout="this.style.background='transparent'"
                                          x-text="ts.time"></span>

                                    <input x-show="editingTimestampIndex === index"
                                           type="time"
                                           step="1"
                                           x-model="editingTimestampValue"
                                           @blur="saveEditedTimestamp(index)"
                                           @keydown.enter="saveEditedTimestamp(index)"
                                           @keydown.escape="cancelEditingTimestamp()"
                                           x-init="if (editingTimestampIndex === index) $el.focus()"
                                           style="padding: 0.2rem 0.4rem; border: 1px solid #3b82f6; border-radius: 3px; font-family: monospace; font-size: 0.85rem;">
                                </div>
                            </div>
                            <button @click="removeManualTimestamp(index)" style="padding: 0.2rem 0.4rem; background: #fee2e2; color: #dc2626; border: none; border-radius: 3px; cursor: pointer; font-size: 0.75rem;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Checkpoint Selection -->
            <div style="margin-bottom: 0.5rem;">
                <label style="display: block; margin-bottom: 0.35rem; font-weight: 600; color: #374151; font-size: 0.85rem;">
                    <i class="bi bi-geo-alt-fill"></i> Point de passage
                </label>
                <select x-model="manualCheckpointId"
                        @change="saveManualCheckpointToStorage()"
                        style="width: 100%; padding: 0.5rem; border: 2px solid #3b82f6; border-radius: 4px; font-size: 0.9rem; font-weight: 500;">
                    <option value="">Sélectionner le checkpoint</option>
                    <option value="DEPART" style="background: #dbeafe; color: #1e40af; font-weight: 600;">🚀 DÉPART</option>
                    <option value="ARRIVEE" style="background: #dcfce7; color: #166534; font-weight: 600;">🏁 ARRIVÉE</option>
                    <option value="ABD" style="background: #fef3c7; color: #92400e; font-weight: 600;">🚫 ABD</option>
                    <template x-for="reader in readers" :key="reader.id">
                        <option :value="reader.id" x-text="reader.location"></option>
                    </template>
                </select>
            </div>

            <!-- Quick Bib Entry with scroll -->
            <div style="background: #f0fdf4; border: 2px solid #22c55e; border-radius: 4px; padding: 0.5rem; margin-bottom: 0.5rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #15803d; font-size: 0.85rem;">
                    <i class="bi bi-pencil-fill"></i> Dossards (ordre des temps)
                </h4>
                <!-- Scrollable list of bib inputs (max 3 lines visible) -->
                <div style="max-height: 120px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.35rem;">
                    <template x-for="(ts, index) in manualTimestamps" :key="index">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="flex-shrink: 0; width: 40px; font-weight: 600; color: #15803d; font-size: 0.8rem;">
                                <span x-text="`#${index + 1}`"></span>
                                <span style="font-size: 0.7rem; margin-left: 0.15rem;" x-text="ts.time"></span>
                            </div>
                            <input type="text"
                                   x-model="manualBibs[index]"
                                   :placeholder="`Dossard ${index + 1}`"
                                   @keydown.enter.prevent="if (index < manualTimestamps.length - 1) $event.target.parentElement.nextElementSibling?.querySelector('input')?.focus(); else importManualTimesQuick()"
                                   style="flex: 1; padding: 0.5rem; border: 2px solid #86efac; border-radius: 4px; font-size: 0.9rem; font-weight: 600;">
                        </div>
                    </template>
                </div>
            </div>

                <!-- OR CSV Import -->
                <details style="margin-bottom: 0.5rem;">
                    <summary style="cursor: pointer; padding: 0.35rem; background: #f3f4f6; border-radius: 4px; font-weight: 500; color: #6b7280; font-size: 0.85rem;">
                        <i class="bi bi-file-earmark-arrow-up"></i> OU importer depuis CSV
                    </summary>
                    <div style="padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; margin-top: 0.35rem;">
                        <input
                            type="file"
                            accept=".csv,.txt"
                            @change="csvFile = $event.target.files[0]"
                            style="width: 100%; padding: 0.35rem; border: 2px dashed #d1d5db; border-radius: 4px; cursor: pointer; font-size: 0.8rem;"
                        >
                        <div x-show="csvFile" style="margin-top: 0.35rem; color: #059669; font-size: 0.8rem;">
                            <i class="bi bi-check-circle-fill"></i>
                            <span x-text="csvFile?.name"></span>
                        </div>
                    </div>
                </details>
            </div>

            <!-- Fixed footer with action buttons -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 0.5rem; display: flex; gap: 0.5rem; justify-content: flex-end; background: white;">
                <button
                    @click="showManualTimesModal = false"
                    style="padding: 0.5rem 1rem; background: #e5e7eb; color: #374151; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;"
                >
                    Annuler
                </button>
                <button
                    @click="csvFile ? importManualTimesFromCSV() : importManualTimesQuick()"
                    :disabled="(!manualCheckpointId || importingManualTimes) || (!csvFile && manualBibs.filter(b => b).length !== manualTimestamps.length)"
                    style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.35rem;"
                    :style="((!manualCheckpointId || importingManualTimes) || (!csvFile && manualBibs.filter(b => b).length !== manualTimestamps.length)) ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                >
                    <i class="bi bi-check-circle-fill"></i>
                    <span x-show="!importingManualTimes">Attribuer</span>
                    <span x-show="importingManualTimes">Attribution...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- RFID File Import Modal -->
    <div x-show="showRfidFileModal" class="modal-overlay" @click.self="showRfidFileModal = false">
        <div class="modal-content" style="max-width: 700px;">
            <h3><i class="bi bi-file-earmark-text-fill"></i> Importer heures</h3>

            <!-- Checkpoint selection -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <i class="bi bi-geo-alt-fill"></i> Checkpoint / Lecteur
                </label>
                <select x-model="rfidCheckpointId"
                        style="width: 100%; padding: 0.75rem; background: #1a1d2e; color: white; border: 1px solid #2a2d3e; border-radius: 6px;">
                    <option value="">Sélectionner le checkpoint</option>
                    <option value="DEPART" style="background: #1e3a8a; color: #93c5fd; font-weight: 600;">🚀 DÉPART</option>
                    <option value="ARRIVEE" style="background: #14532d; color: #86efac; font-weight: 600;">🏁 ARRIVÉE</option>
                    <template x-for="reader in readers" :key="reader.id">
                        <option :value="reader.id" x-text="reader.location"></option>
                    </template>
                </select>
            </div>

            <!-- File upload -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <i class="bi bi-file-earmark-text"></i> Fichier de détections
                </label>
                <input
                    type="file"
                    accept=".txt"
                    @change="rfidFile = $event.target.files[0]"
                    style="width: 100%; padding: 0.75rem; border: 2px dashed #d1d5db; border-radius: 8px; cursor: pointer;"
                >
                <div x-show="rfidFile" style="margin-top: 0.5rem; color: #059669;">
                    <i class="bi bi-check-circle-fill"></i>
                    <span x-text="rfidFile?.name"></span>
                </div>
            </div>

            <!-- Import progress/results -->
            <div x-show="rfidImportResults" style="margin-bottom: 1.5rem; background: #f9fafb; border-radius: 8px; padding: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #059669;">
                    <i class="bi bi-check-circle-fill"></i> Résultat de l'import
                </h4>
                <div style="color: #374151; font-size: 0.9rem;">
                    <div><strong x-text="rfidImportResults?.total || 0"></strong> détections dans le fichier</div>
                    <div style="color: #22c55e;" x-show="rfidImportResults?.created > 0">
                        <strong x-text="rfidImportResults?.created || 0"></strong> nouvelles détections créées
                    </div>
                    <div style="color: #3b82f6;" x-show="rfidImportResults?.updated > 0">
                        <strong x-text="rfidImportResults?.updated || 0"></strong> détections mises à jour
                    </div>
                    <div x-show="rfidImportResults?.errors > 0" style="color: #ef4444;">
                        <strong x-text="rfidImportResults?.errors || 0"></strong> erreurs
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button
                    @click="closeRfidModal()"
                    style="padding: 0.75rem 1.5rem; background: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    <span x-show="!rfidImportResults">Annuler</span>
                    <span x-show="rfidImportResults">Fermer</span>
                </button>
                <button
                    @click="importRfidFile()"
                    :disabled="!rfidFile || !rfidCheckpointId || importingRfidFile"
                    style="padding: 0.75rem 1.5rem; background: #8b5cf6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;"
                    :style="(!rfidFile || !rfidCheckpointId || importingRfidFile) ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                    x-show="!rfidImportResults">
                    <i class="bi bi-upload"></i>
                    <span x-show="!importingRfidFile">Importer</span>
                    <span x-show="importingRfidFile">Import en cours...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function chronoApp() {
    return {
        eventName: '',
        currentEventId: null,
        currentEvent: null,
        alertThreshold: 5,
        currentTime: '00:00:00',
        raceChrono: '00:00:00',
        selectedRaceId: null,
        races: [],
        waves: [],
        categories: [],
        readers: [],
        results: [],
        displayedResults: [],
        selectedResult: null,
        runnerCheckpoints: [],
        runnerAverageSpeed: null,
        editingField: null,
        editingValue: '',
        editingCheckpointId: null,
        searchQuery: '',
        categoryFilter: '',
        sasFilter: '',
        raceFilter: '',
        checkpointFilter: '',
        lapFilter: '',
        sortBy: 'recent',
        loading: false,
        saving: false,
        startingRace: false,
        alertMessage: null,
        toastMessage: null,
        toastType: 'success',
        showTopDepartModal: false,
        showManualTimesModal: false,
        manualBib: '',
        manualTimestamps: [],
        manualBibs: [],
        manualCheckpointId: '',
        editingTimestampIndex: null,
        editingTimestampValue: '',
        csvFile: null,
        importingManualTimes: false,
        singleEntry: {
            bibNumber: '',
            checkpointId: '',
            status: 'normal',
            time: ''
        },
        addingSingleEntry: false,
        intermediateReaderId: '',
        intermediateDate: '',
        intermediateTime: '',
        editingRaceId: null,
        editingWaveId: null,
        editStartDate: '',
        editStartTime: '',
        allWavesMap: {},
        showRfidFileModal: false,
        rfidFile: null,
        rfidCheckpointId: '',
        importingRfidFile: false,
        rfidImportResults: null,
        clockInterval: null,
        autoRefreshInterval: null,
        detectionFlash: false,

        init() {
            this.startClock();
            this.loadEvent().then(() => {
                // Only load races, results, etc. if we have an active event
                if (this.currentEventId) {
                    this.loadAlertThreshold();
                    this.loadManualTimestampsFromStorage();
                    this.loadRaces().then(() => this.autoSelectLastStartedRace());
                    this.loadCategories();
                    this.loadAllResults().then(() => {
                        this.loadAlertsFromStorage();
                    });
                }
            });
            this.startAutoRefresh();
            this.startAlertCheck();
            this.setupKeyboardNavigation();
        },

        setupKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                // Only handle arrow keys if a result is selected
                if (!this.selectedResult) return;

                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.navigateResults(e.key === 'ArrowDown' ? 1 : -1);
                }
            });
        },

        navigateResults(direction) {
            if (!this.selectedResult || this.displayedResults.length === 0) return;

            // Find current index
            const currentIndex = this.displayedResults.findIndex(r => r.id === this.selectedResult.id);
            if (currentIndex === -1) return;

            // Calculate new index
            const newIndex = currentIndex + direction;

            // Check bounds
            if (newIndex < 0 || newIndex >= this.displayedResults.length) return;

            // Select new result
            this.selectResult(this.displayedResults[newIndex]);

            // Scroll to the selected row
            this.$nextTick(() => {
                const selectedRow = document.querySelector('.chrono-table tr.selected');
                if (selectedRow) {
                    selectedRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        },

        startClock() {
            this.updateClock();
            this.clockInterval = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('fr-FR');
            this.updateRaceChrono();
        },

        updateRaceChrono() {
            const race = this.getSelectedRace();
            if (!race || !race.start_time) {
                this.raceChrono = '00:00:00';
                return;
            }

            const startTime = new Date(race.start_time);
            const now = new Date();
            const elapsed = Math.floor((now - startTime) / 1000); // seconds

            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;

            this.raceChrono = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        getWaveChrono(wave) {
            if (!wave || !wave.real_start_time) {
                return '-- : -- : --';
            }

            const startTime = new Date(wave.real_start_time);
            const now = new Date();
            const elapsed = Math.floor((now - startTime) / 1000); // seconds

            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;

            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        getSelectedRace() {
            return this.races.find(r => r.id == this.selectedRaceId);
        },

        // Get waves filtered by selected race
        getFilteredWaves() {
            if (!this.selectedRaceId) {
                return [];
            }
            return this.waves.filter(wave => wave.race_id == this.selectedRaceId);
        },

        switchRaceChrono() {
            this.updateRaceChrono();
            this.loadWaves();
        },

        autoSelectLastStartedRace() {
            // Auto-select the most recently started race
            const startedRaces = this.races.filter(r => r.start_time).sort((a, b) => new Date(b.start_time) - new Date(a.start_time));
            if (startedRaces.length > 0) {
                this.selectedRaceId = startedRaces[0].id;
                this.updateRaceChrono();
            }
        },

        async loadEvent() {
            try {
                // Load only currently active events (is_active=true AND within date range)
                // Add timestamp to prevent caching
                const response = await axios.get('/events/active/list', {
                    params: { _t: Date.now() }
                });

                if (response.data.length === 0) {
                    // No active event - clear everything
                    this.currentEvent = null;
                    this.eventName = 'Aucun événement actif';
                    this.currentEventId = null;
                    this.readers = [];
                    this.races = [];
                    this.waves = [];
                    this.allWavesMap = {};
                    this.categories = [];
                    this.detections = [];
                    this.results = [];
                    this.displayedResults = [];
                    this.selectedRaceId = null;
                    this.selectedCheckpointId = null;
                    this.liveResults = [];
                    this.selectedResult = null;
                    this.runnerCheckpoints = [];
                    this.runnerAverageSpeed = null;
                    this.raceChrono = '00:00:00';
                    this.alertMessage = null;

                    // Clear filters
                    this.searchQuery = '';
                    this.categoryFilter = '';
                    this.sasFilter = '';
                    this.raceFilter = '';
                    this.checkpointFilter = '';
                    this.lapFilter = '';

                    // Clear editing states
                    this.editingField = null;
                    this.editingValue = '';
                    this.editingCheckpointId = null;

                    return;
                }

                // Load first active event
                const activeEvent = response.data[0];
                this.currentEvent = activeEvent;
                this.eventName = activeEvent.name;
                this.currentEventId = activeEvent.id;
                // Reload readers when event is loaded, then load checkpoint
                await this.loadReaders();
                this.loadManualCheckpointFromStorage();
            } catch (error) {
                console.error('Erreur chargement événement', error);
            }
        },

        loadAlertThreshold() {
            if (this.currentEvent && this.currentEvent.alert_threshold_minutes) {
                this.alertThreshold = this.currentEvent.alert_threshold_minutes;
            } else {
                this.alertThreshold = 5; // default
            }
        },

        async loadRaces() {
            // Don't load races if no active event
            if (!this.currentEventId) {
                this.races = [];
                return;
            }

            try {
                // Load only races for current active event
                // Add timestamp to prevent caching
                const response = await axios.get(`/races/event/${this.currentEventId}`, {
                    params: { _t: Date.now() }
                });
                this.races = response.data;
            } catch (error) {
                console.error('Erreur chargement courses', error);
            }
        },

        async loadWaves() {
            // Don't load waves if no race selected
            if (!this.selectedRaceId) {
                this.waves = [];
                return;
            }

            try {
                // Load waves for selected race
                // Add timestamp to prevent caching
                const response = await axios.get(`/waves`, {
                    params: {
                        race_id: this.selectedRaceId,
                        _t: Date.now()
                    }
                });
                this.waves = response.data;
            } catch (error) {
                console.error('Erreur chargement vagues', error);
                this.waves = [];
            }
        },

        async loadCategories() {
            // Don't load categories if no active event
            if (!this.currentEventId) {
                this.categories = [];
                return;
            }

            try {
                // Add timestamp to prevent caching
                const response = await axios.get('/categories', {
                    params: { _t: Date.now() }
                });
                this.categories = response.data;
            } catch (error) {
                console.error('Erreur chargement catégories', error);
            }
        },

        async loadReaders() {
            if (!this.currentEventId) {
                this.readers = [];
                return; // Wait for event to load
            }

            try {
                // Load only readers for current event
                // Add timestamp to prevent caching
                const response = await axios.get(`/readers/event/${this.currentEventId}`, {
                    params: { _t: Date.now() }
                });
                this.readers = response.data;
            } catch (error) {
                console.error('Erreur chargement lecteurs', error);
            }
        },

        async loadAllResults() {
            // Don't load results if no active event
            if (!this.currentEventId) {
                this.results = [];
                this.displayedResults = [];
                return;
            }

            this.loading = true;
            try {
                // Save existing alerts before reload
                const alertsMap = new Map();
                this.results.forEach(r => {
                    if (r.alerts && r.alerts.length > 0) {
                        alertsMap.set(r.id, r.alerts);
                    }
                });

                // Add timestamp to prevent caching
                const response = await axios.get('/results', {
                    params: {
                        timing_mode: true,
                        _t: Date.now()
                    }
                });
                this.results = response.data.sort((a, b) => new Date(b.raw_time) - new Date(a.raw_time));

                // Restore alerts after reload
                this.results.forEach(r => {
                    if (alertsMap.has(r.id)) {
                        r.alerts = alertsMap.get(r.id);
                    }
                });

                this.filterResults();
            } catch (error) {
                console.error('Erreur', error);
            } finally {
                this.loading = false;
            }
        },

        async checkForNewResults() {
            // Don't check for results if no active event
            if (!this.currentEventId) {
                return;
            }

            // Silent check for new results without loading spinner
            try {
                // Add timestamp to prevent caching
                const response = await axios.get('/results', {
                    params: {
                        timing_mode: true,
                        _t: Date.now()
                    }
                });
                const newResults = response.data;

                // Find truly new results (not in current array)
                const existingIds = new Set(this.results.map(r => r.id));
                const addedResults = newResults.filter(r => !existingIds.has(r.id));

                if (addedResults.length > 0) {
                    // Add new results to beginning of array (most recent first)
                    this.results = [...addedResults, ...this.results];

                    // Sort all results by raw_time
                    this.results.sort((a, b) => new Date(b.raw_time) - new Date(a.raw_time));

                    // Re-filter to update display
                    this.filterResults();

                    // Trigger green flash animation
                    this.triggerDetectionFlash();

                    // Check for duplicates and aberrant times
                    addedResults.forEach(result => {
                        this.checkForDuplicates(result);
                        this.checkForAberrantTimes(result);
                    });
                }

                // Update existing results that may have changed (e.g., positions recalculated)
                const updatedResults = newResults.filter(nr => {
                    const existing = this.results.find(r => r.id === nr.id);
                    return existing && (
                        existing.position !== nr.position ||
                        existing.category_position !== nr.category_position ||
                        existing.calculated_time !== nr.calculated_time
                    );
                });

                if (updatedResults.length > 0) {
                    // Update changed results while preserving alerts
                    updatedResults.forEach(nr => {
                        const index = this.results.findIndex(r => r.id === nr.id);
                        if (index !== -1) {
                            // Save alerts before update
                            const existingAlerts = this.results[index].alerts;

                            // Update result with new data
                            this.results[index] = nr;

                            // Restore alerts
                            if (existingAlerts && existingAlerts.length > 0) {
                                this.results[index].alerts = existingAlerts;
                            }
                        }
                    });
                    this.filterResults();
                }

            } catch (error) {
                console.error('Erreur vérification nouveaux résultats:', error);
            }
        },

        triggerDetectionFlash() {
            // Trigger green flash animation around the clock
            this.detectionFlash = true;
            setTimeout(() => {
                this.detectionFlash = false;
            }, 800); // Match animation duration
        },

        checkForDuplicates(newResult) {
            // Check if there's a duplicate detection (same runner, same checkpoint, <10 seconds apart)
            if (!newResult.entrant_id || !newResult.reader_location) {
                return;
            }

            const duplicates = this.results.filter(r => {
                if (r.id === newResult.id) return false; // Don't compare with itself
                if (r.entrant_id !== newResult.entrant_id) return false;
                if (r.reader_location !== newResult.reader_location) return false;

                // Calculate time difference in seconds
                const timeDiff = Math.abs(new Date(newResult.raw_time) - new Date(r.raw_time)) / 1000;
                return timeDiff < 10;
            });

            if (duplicates.length > 0) {
                const duplicate = duplicates[0];
                const timeDiff = Math.abs(new Date(newResult.raw_time) - new Date(duplicate.raw_time)) / 1000;

                this.addAlertToResult(newResult, {
                    type: 'duplicate',
                    icon: '!',
                    title: 'Passage multiple détecté',
                    details: `Détection multiple au point "${newResult.reader_location}" (intervalle: ${timeDiff.toFixed(1)}s). Vérifier si doublon ou erreur antenne.`,
                    status: 'pending'
                });
            }
        },

        checkForAberrantTimes(result) {
            // Check for negative times
            if (result.calculated_time && result.calculated_time < 0) {
                this.addAlertToResult(result, {
                    type: 'negative-time',
                    icon: '×',
                    title: 'Temps négatif détecté',
                    details: `Temps calculé négatif (${result.calculated_time}s). Le TOP DÉPART semble postérieur à cette détection. Vérifier l'heure de départ de la course.`,
                    status: 'pending'
                });
            }

            // Calculate adaptive speed threshold based on exact race distance
            // 4-zone formula calibrated on REAL race data from actual events
            // Goal: Alert on suspicious speeds without constant false positives
            let speedThreshold = 18.0; // Default if distance unknown
            let distanceLabel = 'estimée';
            const speedAbsoluteMax = 35.0; // Sprint WR peak ~37 km/h, impossible to sustain

            if (result.race?.distance) {
                const distance = parseFloat(result.race.distance);

                // 4-zone adaptive formula based on real winner speeds + 10% margin:
                // Real data: 5km→21.1, 10km→19.7, 18km→16.9, 20km→18.5, 100km→7.41 km/h
                if (distance <= 2) {
                    // Zone 1: Middle-distance (800m-2km) - No real data, extrapolated
                    // Examples: 0.8km→27, 1km→26.5, 1.5km→25.8, 2km→25
                    speedThreshold = 28 - (1.5 * distance);
                } else if (distance < 10) {
                    // Zone 2: Short endurance (2-10km)
                    // Real: 5km→21.1 (threshold 23), 10km→19.7 (threshold 22)
                    // Examples: 2km→25, 5km→24, 8km→22.6, 10km→22
                    speedThreshold = 25 - (0.4 * (distance - 2));
                } else if (distance < 30) {
                    // Zone 3a: Medium endurance (10-30km)
                    // Real: 10km→19.7 (threshold 22), 18km→16.9 (threshold 21), 20km→18.5 (threshold 20)
                    // Examples: 10km→22, 15km→21.2, 20km→20.5, 25km→19.8, 30km→19
                    speedThreshold = 22 - (0.15 * (distance - 10));
                } else {
                    // Zone 3b: Ultra-endurance (30km+)
                    // Real: 100km→7.41 (threshold 8.5)
                    // Examples: 30km→19, 42km→17.2, 60km→14.5, 100km→8.5
                    speedThreshold = 19 - (0.15 * (distance - 30));
                }

                // Minimum threshold for ultra-long distances (100km+)
                speedThreshold = Math.max(speedThreshold, 8.0);

                // Round to 1 decimal for display
                speedThreshold = Math.round(speedThreshold * 10) / 10;
                distanceLabel = distance + ' km';
            }

            // Check speed from result.speed field (if available)
            if (result.speed) {
                const speed = parseFloat(result.speed);

                if (speed > speedAbsoluteMax) {
                    // Critical: Physically impossible for any sustained running
                    this.addAlertToResult(result, {
                        type: 'speed',
                        icon: '⚠',
                        title: 'Vitesse physiquement impossible',
                        details: `Vitesse de ${speed.toFixed(1)} km/h détectée (seuil absolu: ${speedAbsoluteMax} km/h). Vitesse impossible pour un humain sur longue distance. Erreur certaine dans les horaires de passage ou confusion de coureur.`,
                        status: 'pending'
                    });
                } else if (speed > speedThreshold) {
                    // Warning: Exceptional for this distance
                    this.addAlertToResult(result, {
                        type: 'speed',
                        icon: '▲',
                        title: 'Vitesse élevée pour la distance',
                        details: `Vitesse moyenne de ${speed.toFixed(1)} km/h (seuil ${distanceLabel}: ${speedThreshold} km/h). Vitesse au-delà d'un excellent coureur amateur pour cette distance. Vérifier les horaires de passage.`,
                        status: 'pending'
                    });
                }
            }

            // Additional check: calculate speed between checkpoints if we have previous results
            if (result.entrant_id && result.reader_id) {
                const runnerResults = this.results
                    .filter(r => r.entrant_id === result.entrant_id && r.reader_id !== result.reader_id)
                    .sort((a, b) => new Date(a.raw_time) - new Date(b.raw_time));

                if (runnerResults.length > 0) {
                    const previousResult = runnerResults[runnerResults.length - 1];
                    const currentReader = this.readers.find(r => r.id === result.reader_id);
                    const previousReader = this.readers.find(r => r.id === previousResult.reader_id);

                    if (currentReader && previousReader && currentReader.distance_from_start && previousReader.distance_from_start) {
                        const distance = Math.abs(currentReader.distance_from_start - previousReader.distance_from_start);
                        const timeMs = new Date(result.raw_time) - new Date(previousResult.raw_time);
                        const timeHours = timeMs / (1000 * 60 * 60);

                        if (timeHours > 0) {
                            const calculatedSpeed = distance / timeHours;

                            if (calculatedSpeed > speedAbsoluteMax) {
                                // Critical: Physically impossible
                                this.addAlertToResult(result, {
                                    type: 'speed',
                                    icon: '⚠',
                                    title: 'Vitesse physiquement impossible',
                                    details: `Vitesse calculée de ${calculatedSpeed.toFixed(1)} km/h entre "${previousReader.location}" et "${currentReader.location}" (seuil absolu: ${speedAbsoluteMax} km/h). Erreur certaine dans les horaires ou confusion de coureur.`,
                                    status: 'pending'
                                });
                            } else if (calculatedSpeed > speedThreshold) {
                                // Warning: Exceptional
                                this.addAlertToResult(result, {
                                    type: 'speed',
                                    icon: '▲',
                                    title: 'Vitesse élevée entre points de passage',
                                    details: `Vitesse calculée de ${calculatedSpeed.toFixed(1)} km/h entre "${previousReader.location}" et "${currentReader.location}" (seuil ${distanceLabel}: ${speedThreshold} km/h). Vérifier les horaires de passage.`,
                                    status: 'pending'
                                });
                            }
                        }
                    }
                }
            }
        },

        addAlertToResult(result, alert) {
            // Initialize alerts array if it doesn't exist
            if (!result.alerts) {
                result.alerts = [];
            }

            // Check if this alert type already exists for this result
            const existingAlert = result.alerts.find(a => a.type === alert.type && a.details === alert.details);
            if (!existingAlert) {
                result.alerts.push({
                    id: Date.now() + Math.random(),
                    ...alert
                });
                this.saveAlertsToStorage(); // Persist to localStorage
            }
        },

        getPendingAlertsCount() {
            let count = 0;
            this.results.forEach(result => {
                if (result.alerts && result.alerts.length > 0) {
                    count += result.alerts.filter(a => a.status === 'pending').length;
                }
            });
            return count;
        },

        getResultAlertClass(result) {
            if (!result.alerts || result.alerts.length === 0) return '';

            const pendingAlerts = result.alerts.filter(a => a.status === 'pending');
            if (pendingAlerts.length === 0) return '';

            // Priority: negative-time > speed > duplicate
            if (pendingAlerts.some(a => a.type === 'negative-time')) return 'has-alert-negative-time';
            if (pendingAlerts.some(a => a.type === 'speed')) return 'has-alert-speed';
            if (pendingAlerts.some(a => a.type === 'duplicate')) return 'has-alert-duplicate';
            return '';
        },

        markAlertAsVerified(result, alertId) {
            if (!result.alerts) return;
            const alert = result.alerts.find(a => a.id === alertId);
            if (alert) {
                alert.status = 'verified';
                this.saveAlertsToStorage(); // Persist to localStorage
                this.filterResults(); // Refresh display
            }
        },

        markAlertAsIgnored(result, alertId) {
            if (!result.alerts) return;
            const alert = result.alerts.find(a => a.id === alertId);
            if (alert) {
                alert.status = 'ignored';
                this.saveAlertsToStorage(); // Persist to localStorage
                this.filterResults(); // Refresh display
            }
        },

        saveAlertsToStorage() {
            // Save all alerts to localStorage
            const alertsData = {};
            this.results.forEach(result => {
                if (result.alerts && result.alerts.length > 0) {
                    alertsData[result.id] = result.alerts;
                }
            });
            try {
                localStorage.setItem('chronofront_alerts', JSON.stringify(alertsData));
            } catch (error) {
                console.error('Erreur sauvegarde alertes localStorage:', error);
            }
        },

        loadAlertsFromStorage() {
            // Restore alerts from localStorage
            try {
                const alertsJson = localStorage.getItem('chronofront_alerts');
                if (!alertsJson) return;

                const alertsData = JSON.parse(alertsJson);
                this.results.forEach(result => {
                    if (alertsData[result.id]) {
                        result.alerts = alertsData[result.id];
                    }
                });
                this.filterResults(); // Refresh display with alerts
            } catch (error) {
                console.error('Erreur chargement alertes localStorage:', error);
            }
        },

        recalculateAllAlerts() {
            // Clear all existing alerts and recalculate based on current data
            // This is called after TOP DEPART modification to re-evaluate anomalies
            console.log('Recalcul de toutes les alertes...');

            // Clear all alerts
            this.results.forEach(result => {
                result.alerts = [];
            });

            // Recalculate alerts for all results
            this.results.forEach(result => {
                this.checkForDuplicates(result);
                this.checkForAberrantTimes(result);
            });

            // Save to localStorage
            this.saveAlertsToStorage();

            // Refresh display
            this.filterResults();

            const alertCount = this.getPendingAlertsCount();
            console.log(`Recalcul terminé: ${alertCount} alerte(s) détectée(s)`);
        },

        normalizeString(str) {
            if (!str) return '';
            // NFD normalization: decompose accented characters
            // Then remove diacritics (combining marks)
            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        },

        async filterResults() {
            // Check if any filters are active
            const hasActiveFilters = this.searchQuery || this.categoryFilter || this.sasFilter || this.raceFilter || this.checkpointFilter || this.lapFilter;

            if (hasActiveFilters) {
                // When filters are active, fetch from API to search ALL results
                await this.fetchFilteredResults();
            } else {
                // No filters: filter locally from cached results (last 500)
                this.displayedResults = this.results;
            }

            // Calculate positions after filtering
            this.calculatePositions();

            // Apply sorting
            this.sortResults();
        },

        async fetchFilteredResults() {
            try {
                // Build query parameters
                const params = new URLSearchParams();

                if (this.searchQuery) {
                    params.append('search', this.searchQuery);
                }
                if (this.categoryFilter) {
                    params.append('category', this.categoryFilter);
                }
                if (this.sasFilter) {
                    params.append('wave', this.sasFilter);
                }
                if (this.raceFilter) {
                    // Find race ID from race name
                    const race = this.races.find(r => r.name === this.raceFilter);
                    if (race) {
                        params.append('race_id', race.id);
                    }
                }
                if (this.checkpointFilter) {
                    params.append('checkpoint', this.checkpointFilter);
                }
                if (this.lapFilter) {
                    params.append('lap_number', this.lapFilter);
                }
                params.append('timing_mode', 'true');

                const response = await axios.get(`/results?${params.toString()}`);
                this.displayedResults = response.data.sort((a, b) => new Date(b.raw_time) - new Date(a.raw_time));
            } catch (error) {
                console.error('Erreur lors de la recherche filtrée:', error);
                // Fallback to local filtering
                this.displayedResults = this.results.filter(result => {
                    if (this.searchQuery) {
                        const searchNormalized = this.normalizeString(this.searchQuery);
                        const bibNumber = result.entrant?.bib_number?.toString() || '';
                        const fullName = (result.entrant?.firstname || '') + ' ' + (result.entrant?.lastname || '');
                        const fullNameNormalized = this.normalizeString(fullName);

                        const matchesSearch = bibNumber.includes(this.searchQuery) ||
                            fullNameNormalized.includes(searchNormalized);

                        if (!matchesSearch) return false;
                    }
                    if (this.categoryFilter && result.entrant?.category?.name !== this.categoryFilter) return false;
                    if (this.sasFilter && result.wave?.name !== this.sasFilter) return false;
                    if (this.raceFilter && result.race?.name !== this.raceFilter) return false;
                    if (this.checkpointFilter && result.reader_location !== this.checkpointFilter) return false;
                    if (this.lapFilter && result.lap_number != this.lapFilter) return false;
                    return true;
                });
            }
        },

        sortResults() {
            switch (this.sortBy) {
                case 'position':
                    // Sort by position (lowest first)
                    this.displayedResults.sort((a, b) => {
                        const posA = a.position || 9999;
                        const posB = b.position || 9999;
                        return posA - posB;
                    });
                    break;

                case 'time':
                    // Sort by calculated time (fastest first)
                    this.displayedResults.sort((a, b) => {
                        const timeA = a.calculated_time || 999999;
                        const timeB = b.calculated_time || 999999;
                        return timeA - timeB;
                    });
                    break;

                case 'recent':
                default:
                    // Sort by raw_time (most recent first)
                    this.displayedResults.sort((a, b) => {
                        return new Date(b.raw_time) - new Date(a.raw_time);
                    });
                    break;
            }
        },

        calculatePositions() {
            // Group results by race and entrant to get best result for each runner
            const resultsByRace = {};

            this.displayedResults.forEach(result => {
                if (!result.calculated_time || !result.race_id || !result.entrant_id) {
                    result.position = null;
                    result.category_position = null;
                    return;
                }

                const raceKey = result.race_id;
                if (!resultsByRace[raceKey]) {
                    resultsByRace[raceKey] = {};
                }

                const entrantKey = result.entrant_id;
                // Keep only the best time (lowest calculated_time) for each entrant
                if (!resultsByRace[raceKey][entrantKey] ||
                    result.calculated_time < resultsByRace[raceKey][entrantKey].calculated_time) {
                    resultsByRace[raceKey][entrantKey] = result;
                }
            });

            // Calculate positions for each race
            Object.keys(resultsByRace).forEach(raceKey => {
                const raceResults = Object.values(resultsByRace[raceKey]);

                // Sort by calculated time (fastest first)
                raceResults.sort((a, b) => a.calculated_time - b.calculated_time);

                // Assign overall positions
                raceResults.forEach((result, index) => {
                    result.position = index + 1;
                });

                // Calculate category positions
                const categorizedResults = {};
                raceResults.forEach(result => {
                    const categoryId = result.entrant?.category?.id;
                    if (!categoryId) return;

                    if (!categorizedResults[categoryId]) {
                        categorizedResults[categoryId] = [];
                    }
                    categorizedResults[categoryId].push(result);
                });

                // Assign category positions
                Object.values(categorizedResults).forEach(categoryResults => {
                    categoryResults.forEach((result, index) => {
                        result.category_position = index + 1;
                    });
                });
            });

            // Reset positions for results not in best results
            this.displayedResults.forEach(result => {
                if (!result.position && result.calculated_time) {
                    result.position = '-';
                    result.category_position = '-';
                }
            });
        },

        hasOngoingRaces() {
            return this.races.some(r => r.start_time && !r.end_time);
        },

        selectResult(result) {
            this.selectedResult = result;
            this.calculateRunnerCheckpoints();
        },

        calculateRunnerCheckpoints() {
            if (!this.selectedResult || !this.selectedResult.entrant_id) {
                this.runnerCheckpoints = [];
                this.runnerAverageSpeed = null;
                return;
            }

            // Check if this is a multi-lap race (n_laps or infinite_loop)
            const runnerRace = this.selectedResult.race;
            const isMultiLap = runnerRace && (runnerRace.type === 'n_laps' || runnerRace.type === 'infinite_loop');

            if (isMultiLap) {
                // FOR MULTI-LAP RACES: Show all laps/passages for this runner
                const runnerResults = this.results
                    .filter(r => r.entrant_id === this.selectedResult.entrant_id)
                    .sort((a, b) => (a.lap_number || 0) - (b.lap_number || 0));

                this.runnerCheckpoints = [];

                // Add race start with priority: entrant.start_time > wave.real_start_time > wave.start_time > race.start_time
                const runnerWave = this.selectedResult.wave;
                const runnerEntrant = this.selectedResult.entrant;

                // Determine start time with priority
                let startTime = null;
                let startLabel = 'DÉPART';

                if (runnerEntrant && runnerEntrant.start_time) {
                    // Priority 1: Individual start time (detected in DEPART window)
                    // start_time is TIME only (HH:MM:SS), combine with result date
                    const resultDate = new Date(this.selectedResult.raw_time).toISOString().split('T')[0];
                    startTime = `${resultDate}T${runnerEntrant.start_time}`;
                    startLabel = 'DÉPART (détecté)';
                } else if (runnerWave && runnerWave.real_start_time) {
                    // Priority 2: Wave TOP départ (actual)
                    startTime = runnerWave.real_start_time;
                    startLabel = 'DÉPART (TOP vague)';
                } else if (runnerWave && runnerWave.start_time) {
                    // Priority 3: Wave planned start
                    startTime = runnerWave.start_time;
                    startLabel = 'DÉPART (vague planifié)';
                } else if (runnerRace && runnerRace.start_time) {
                    // Priority 4: Race TOP départ
                    startTime = runnerRace.start_time;
                    startLabel = 'DÉPART (course)';
                }

                if (startTime) {
                    this.runnerCheckpoints.push({
                        id: null,
                        location: startLabel,
                        lap_number: null,
                        distance: 0,
                        time_display: this.formatTime(startTime),
                        lap_time_display: null,
                        raw_time: new Date(startTime),
                        is_estimated: false
                    });
                }

                // Add each lap
                runnerResults.forEach((result, index) => {
                    // Calculate lap speed: distance / (lap_time / 3600)
                    let lapSpeed = null;
                    if (result.lap_time && runnerRace.distance > 0) {
                        const hours = result.lap_time / 3600;
                        lapSpeed = (runnerRace.distance / hours).toFixed(2);
                    }

                    this.runnerCheckpoints.push({
                        id: result.id,
                        location: `Tour ${result.lap_number || (index + 1)}`,
                        lap_number: result.lap_number || (index + 1),
                        distance: (result.lap_number || (index + 1)) * (runnerRace.distance || 0),
                        time_display: this.formatTime(result.raw_time),
                        lap_time_display: this.formatDuration(result.lap_time),
                        calculated_time_display: this.formatDuration(result.calculated_time),
                        speed: lapSpeed,
                        raw_time: new Date(result.raw_time),
                        is_estimated: false
                    });
                });

                // Calculate average speed for multi-lap
                if (runnerResults.length > 0 && runnerRace.distance) {
                    const lastResult = runnerResults[runnerResults.length - 1];
                    const totalDistance = (lastResult.lap_number || runnerResults.length) * runnerRace.distance;
                    const totalTimeSeconds = lastResult.calculated_time || 0;
                    if (totalTimeSeconds > 0) {
                        this.runnerAverageSpeed = (totalDistance / (totalTimeSeconds / 3600));
                    }
                }

            } else {
                // FOR SINGLE-PASSAGE RACES: Use checkpoint logic (original code)
                const runnerResults = this.results.filter(r => r.entrant_id === this.selectedResult.entrant_id);

                // Get configured readers sorted by distance
                const sortedReaders = [...this.readers]
                    .filter(r => r.distance_from_start !== undefined)
                    .sort((a, b) => parseFloat(a.distance_from_start || 0) - parseFloat(b.distance_from_start || 0));

                if (sortedReaders.length === 0) {
                    this.runnerCheckpoints = [];
                    return;
                }

                // Build checkpoint list
                this.runnerCheckpoints = [];
                let lastRealCheckpoint = null;

                // Add race start as first checkpoint with priority: entrant.start_time > wave.real_start_time > wave.start_time > race.start_time
                const runnerWave = this.selectedResult.wave;
                const runnerEntrant = this.selectedResult.entrant;

                // Determine start time with priority
                let startTime = null;
                let startLabel = 'DÉPART';

                if (runnerEntrant && runnerEntrant.start_time) {
                    // Priority 1: Individual start time (detected in DEPART window)
                    // start_time is TIME only (HH:MM:SS), combine with result date
                    const resultDate = new Date(this.selectedResult.raw_time).toISOString().split('T')[0];
                    startTime = `${resultDate}T${runnerEntrant.start_time}`;
                    startLabel = 'DÉPART (détecté)';
                } else if (runnerWave && runnerWave.real_start_time) {
                    // Priority 2: Wave TOP départ (actual)
                    startTime = runnerWave.real_start_time;
                    startLabel = 'DÉPART (TOP vague)';
                } else if (runnerWave && runnerWave.start_time) {
                    // Priority 3: Wave planned start
                    startTime = runnerWave.start_time;
                    startLabel = 'DÉPART (vague planifié)';
                } else if (runnerRace && runnerRace.start_time) {
                    // Priority 4: Race TOP départ
                    startTime = runnerRace.start_time;
                    startLabel = 'DÉPART (course)';
                }

                if (startTime) {
                    this.runnerCheckpoints.push({
                        id: null,
                        location: startLabel,
                        distance: 0,
                        time_display: this.formatTime(startTime),
                        raw_time: new Date(startTime),
                        is_estimated: false
                    });
                    lastRealCheckpoint = {
                        distance: 0,
                        raw_time: new Date(startTime)
                    };
                }

                // Process each reader checkpoint
                for (let reader of sortedReaders) {
                    // Find if runner was detected at this checkpoint
                    const detection = runnerResults.find(r => r.reader_id === reader.id || r.reader_location === reader.location);

                    if (detection && detection.raw_time) {
                        // Real detection
                        this.runnerCheckpoints.push({
                            id: detection.id,
                            location: reader.location,
                            distance: reader.distance_from_start,
                            time_display: this.formatTime(detection.raw_time),
                            raw_time: new Date(detection.raw_time),
                            is_estimated: false
                        });
                        lastRealCheckpoint = {
                            distance: parseFloat(reader.distance_from_start),
                            raw_time: new Date(detection.raw_time)
                        };
                    } else if (lastRealCheckpoint) {
                        // Estimate time based on average speed
                        const estimatedTime = this.estimateCheckpointTime(lastRealCheckpoint, reader.distance_from_start);
                        if (estimatedTime) {
                            this.runnerCheckpoints.push({
                                id: null,
                                location: reader.location,
                                distance: reader.distance_from_start,
                                time_display: this.formatTime(estimatedTime.toISOString()),
                                raw_time: estimatedTime,
                                is_estimated: true
                            });
                        }
                    }
                }

                // Calculate average speed
                this.calculateAverageSpeed();
            }
        },

        estimateCheckpointTime(lastCheckpoint, targetDistance) {
            // Find the next real checkpoint after lastCheckpoint to calculate average speed
            const nextRealIndex = this.runnerCheckpoints.findIndex(cp =>
                !cp.is_estimated && cp.distance > lastCheckpoint.distance
            );

            let averageSpeed = null;

            if (nextRealIndex > 0) {
                // Calculate speed between last two real checkpoints
                const nextReal = this.runnerCheckpoints[nextRealIndex];
                const distance = nextReal.distance - lastCheckpoint.distance; // km
                const timeMs = nextReal.raw_time - lastCheckpoint.raw_time;
                const timeHours = timeMs / (1000 * 60 * 60);
                averageSpeed = distance / timeHours; // km/h
            } else {
                // Use a default speed estimate if we don't have next checkpoint yet
                // Calculate from all available checkpoints
                averageSpeed = this.runnerAverageSpeed || 10; // default 10 km/h
            }

            if (!averageSpeed || averageSpeed <= 0) return null;

            // Calculate estimated time
            const distanceDiff = targetDistance - lastCheckpoint.distance;
            const timeNeededHours = distanceDiff / averageSpeed;
            const timeNeededMs = timeNeededHours * 60 * 60 * 1000;

            return new Date(lastCheckpoint.raw_time.getTime() + timeNeededMs);
        },

        calculateAverageSpeed() {
            // Calculate overall average speed from all real checkpoints
            const realCheckpoints = this.runnerCheckpoints.filter(cp => !cp.is_estimated);
            if (realCheckpoints.length < 2) {
                this.runnerAverageSpeed = null;
                return;
            }

            const first = realCheckpoints[0];
            const last = realCheckpoints[realCheckpoints.length - 1];
            const distance = last.distance - first.distance; // km
            const timeMs = last.raw_time - first.raw_time;
            const timeHours = timeMs / (1000 * 60 * 60);

            this.runnerAverageSpeed = distance / timeHours; // km/h
        },

        // Edit runner field functions
        startEditField(field, currentValue, checkpointId = null) {
            this.editingField = field;
            this.editingValue = currentValue || '';
            this.editingCheckpointId = checkpointId;
        },

        cancelEditField() {
            this.editingField = null;
            this.editingValue = '';
            this.editingCheckpointId = null;
        },

        async saveEditField() {
            if (!this.selectedResult || !this.editingField) return;

            const field = this.editingField;
            const value = this.editingValue;
            const selectedResultId = this.selectedResult.id;

            this.saving = true;
            try {
                if (field === 'bib_number' || field === 'firstname' || field === 'lastname') {
                    // Update entrant
                    await axios.put(`/entrants/${this.selectedResult.entrant.id}`, {
                        [field]: value
                    });
                } else if (field === 'category_id') {
                    // Update entrant category
                    await axios.put(`/entrants/${this.selectedResult.entrant.id}`, {
                        category_id: parseInt(value)
                    });
                } else if (field === 'race_id') {
                    // Update result race
                    await axios.put(`/results/${this.selectedResult.id}`, {
                        race_id: parseInt(value)
                    });
                } else if (field.startsWith('lap_time_') && this.editingCheckpointId) {
                    // Update lap time for specific checkpoint
                    const seconds = this.parseTimeToSeconds(value);
                    await axios.put(`/results/${this.editingCheckpointId}`, {
                        lap_time: seconds
                    });
                }

                this.cancelEditField();

                // Reload all results and re-select the current result
                await this.loadAllResults();

                // Re-select the result to update the panel
                const updatedResult = this.results.find(r => r.id === selectedResultId);
                if (updatedResult) {
                    this.selectResult(updatedResult);
                }

                this.showToast('Modification enregistrée', 'success');
            } catch (error) {
                console.error('Error saving field:', error);
                this.showToast('Erreur lors de la sauvegarde', 'error');
            } finally {
                this.saving = false;
            }
        },

        parseTimeToSeconds(timeStr) {
            // Parse HH:MM:SS or MM:SS to seconds
            const parts = timeStr.split(':').map(p => parseInt(p) || 0);
            if (parts.length === 3) {
                return parts[0] * 3600 + parts[1] * 60 + parts[2];
            } else if (parts.length === 2) {
                return parts[0] * 60 + parts[1];
            }
            return parseInt(timeStr) || 0;
        },

        async addManualTime() {
            if (!this.manualBib) return;
            this.saving = true;
            try {
                await axios.post('/results/time', {
                    bib_number: this.manualBib,
                    is_manual: true
                });
                this.showToast(`Temps enregistré pour ${this.manualBib}`, 'success');
                this.manualBib = '';
                await this.loadAllResults();
            } catch (error) {
                this.showToast('Erreur', 'error');
            } finally {
                this.saving = false;
            }
        },

        async topDepart(race) {
            this.startingRace = true;
            try {
                await axios.post(`/races/${race.id}/start`);
                race.start_time = new Date().toISOString();
                this.showToast(`TOP DÉPART donné pour ${race.name}`, 'success');
                await this.loadRaces();
            } catch (error) {
                this.showToast('Erreur lors du TOP DÉPART', 'error');
            } finally {
                this.startingRace = false;
            }
        },

        startEditingStartTime(race) {
            // Parse current start time to populate inputs
            if (race.start_time) {
                const startDate = new Date(race.start_time);
                const year = startDate.getFullYear();
                const month = String(startDate.getMonth() + 1).padStart(2, '0');
                const day = String(startDate.getDate()).padStart(2, '0');
                const hours = String(startDate.getHours()).padStart(2, '0');
                const minutes = String(startDate.getMinutes()).padStart(2, '0');
                const seconds = String(startDate.getSeconds()).padStart(2, '0');

                this.editStartDate = `${year}-${month}-${day}`;
                this.editStartTime = `${hours}:${minutes}:${seconds}`;
            }
            this.editingRaceId = race.id;
        },

        cancelEditingStartTime() {
            this.editingRaceId = null;
            this.editStartDate = '';
            this.editStartTime = '';
        },

        async saveStartTime(race) {
            if (!this.editStartDate || !this.editStartTime) return;

            const newStartTime = `${this.editStartDate} ${this.editStartTime}`;

            if (!confirm(`Modifier le départ de "${race.name}" à ${this.editStartTime} ?`)) return;

            this.startingRace = true;

            try {
                const response = await axios.put(`/races/${race.id}/start`, {
                    start_time: newStartTime
                });

                const recalculatedCount = response.data.recalculated_results || 0;
                this.showToast(`Départ modifié pour ${race.name} - ${recalculatedCount} résultats recalculés`, 'success');

                // Reload races and results to show updated data
                await this.loadRaces();
                await this.loadAllResults();

                // Recalculate all alerts with new start time
                this.recalculateAllAlerts();

                this.cancelEditingStartTime();

            } catch (error) {
                console.error('Erreur modification départ:', error);
                this.showToast('Erreur lors de la modification', 'error');
            } finally {
                this.startingRace = false;
            }
        },

        // ====== WAVES MANAGEMENT ======
        async loadAllWavesForModal() {
            // Load all waves for all races to display in the modal
            this.allWavesMap = {};
            for (const race of this.races) {
                try {
                    const response = await axios.get(`/waves`, {
                        params: {
                            race_id: race.id,
                            _t: Date.now()
                        }
                    });
                    this.allWavesMap[race.id] = response.data;
                } catch (error) {
                    console.error(`Erreur chargement vagues pour course ${race.id}`, error);
                    this.allWavesMap[race.id] = [];
                }
            }
        },

        async openTopDepartModal() {
            // Ensure races are loaded
            if (this.races.length === 0) {
                await this.loadRaces();
            }
            // Load waves for all races
            await this.loadAllWavesForModal();
            // Open modal
            this.showTopDepartModal = true;
        },

        async topDepartWave(wave) {
            this.startingRace = true;
            try {
                await axios.post(`/waves/${wave.id}/top-depart`);
                wave.real_start_time = new Date().toISOString();
                this.showToast(`TOP DÉPART donné pour ${wave.name}`, 'success');
                await this.loadWaves(); // Reload waves for the selected race
                await this.loadAllWavesForModal(); // Reload modal waves
            } catch (error) {
                console.error('Erreur TOP DÉPART vague:', error);
                this.showToast('Erreur lors du TOP DÉPART', 'error');
            } finally {
                this.startingRace = false;
            }
        },

        // Format ISO datetime to DD/MM/YYYY HH:MM:SS for input display
        formatTimeInput(datetime) {
            if (!datetime) return '';
            const date = new Date(datetime);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
        },

        // Update race start time from inline edit
        async updateRaceStartTime(race, timeString) {
            if (!timeString || timeString === '--/--/---- --:--:--') return;

            // Parse DD/MM/YYYY HH:MM:SS
            const timeMatch = timeString.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})$/);
            if (!timeMatch) {
                this.showToast('Format invalide. Utilisez DD/MM/YYYY HH:MM:SS', 'error');
                return;
            }

            const [, day, month, year, hours, minutes, seconds] = timeMatch;

            try {
                // Build ISO datetime
                const newStartTime = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;

                // Save to API
                await axios.put(`/races/${race.id}/start`, {
                    start_time: newStartTime
                });

                race.start_time = newStartTime;
                this.showToast(`Heure de départ mise à jour pour ${race.name}`, 'success');
                await this.loadRaces();
            } catch (error) {
                console.error('Erreur mise à jour départ:', error);
                this.showToast('Erreur lors de la mise à jour', 'error');
            }
        },

        // Update wave start time from inline edit
        async updateWaveStartTime(wave, timeString) {
            if (!timeString || timeString === '--/--/---- --:--:--') return;

            // Parse DD/MM/YYYY HH:MM:SS
            const timeMatch = timeString.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})$/);
            if (!timeMatch) {
                this.showToast('Format invalide. Utilisez DD/MM/YYYY HH:MM:SS', 'error');
                return;
            }

            const [, day, month, year, hours, minutes, seconds] = timeMatch;

            try {
                // Build ISO datetime
                const newStartTime = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;

                // Save to API
                await axios.post(`/waves/${wave.id}/update-real-start-time`, {
                    real_start_time: newStartTime
                });

                wave.real_start_time = newStartTime;
                this.showToast(`Heure de départ mise à jour pour ${wave.name}`, 'success');
                await this.loadWaves();
                await this.loadAllWavesForModal();
            } catch (error) {
                console.error('Erreur mise à jour départ vague:', error);
                this.showToast('Erreur lors de la mise à jour', 'error');
            }
        },

        startEditingWaveStartTime(wave) {
            // Parse current start time to populate inputs
            if (wave.real_start_time) {
                const startDate = new Date(wave.real_start_time);
                const year = startDate.getFullYear();
                const month = String(startDate.getMonth() + 1).padStart(2, '0');
                const day = String(startDate.getDate()).padStart(2, '0');
                const hours = String(startDate.getHours()).padStart(2, '0');
                const minutes = String(startDate.getMinutes()).padStart(2, '0');
                const seconds = String(startDate.getSeconds()).padStart(2, '0');

                this.editStartDate = `${year}-${month}-${day}`;
                this.editStartTime = `${hours}:${minutes}:${seconds}`;
            }
            this.editingWaveId = wave.id;
        },

        cancelEditingWaveStartTime() {
            this.editingWaveId = null;
            this.editStartDate = '';
            this.editStartTime = '';
        },

        async saveWaveStartTime(wave) {
            if (!this.editStartDate || !this.editStartTime) return;

            const newStartTime = `${this.editStartDate} ${this.editStartTime}`;

            if (!confirm(`Modifier le départ de "${wave.name}" à ${this.editStartTime} ?`)) return;

            this.startingRace = true;

            try {
                const response = await axios.post(`/waves/${wave.id}/update-real-start-time`, {
                    real_start_time: newStartTime
                });

                this.showToast(`Départ modifié pour ${wave.name}`, 'success');

                // Reload waves and results
                await this.loadWaves();
                await this.loadAllWavesForModal();
                await this.loadAllResults();

                // Recalculate all alerts with new start time
                this.recalculateAllAlerts();

                this.cancelEditingWaveStartTime();

            } catch (error) {
                console.error('Erreur modification départ vague:', error);
                this.showToast('Erreur lors de la modification', 'error');
            } finally {
                this.startingRace = false;
            }
        },

        startAutoRefresh() {
            // Auto-refresh every 1 second for real-time updates
            // Always check for new results, even without TOP DÉPART (for contre-la-montre with individual start times)
            this.autoRefreshInterval = setInterval(() => {
                this.checkForNewResults();
            }, 1000);

            // Check every 5 seconds if event is still active (reduced from 30s for faster response)
            setInterval(() => {
                this.checkEventStatus();
            }, 5000);
        },

        async checkEventStatus() {
            try {
                // Add timestamp to prevent caching
                const response = await axios.get('/events/active/list', {
                    params: { _t: Date.now() }
                });

                // If no active event and we currently have one loaded, clear the interface
                if (response.data.length === 0 && this.currentEventId) {
                    // Clear ALL data to have a clean interface
                    this.currentEvent = null;
                    this.eventName = 'Aucun événement actif';
                    this.currentEventId = null;
                    this.readers = [];
                    this.races = [];
                    this.waves = [];
                    this.allWavesMap = {};
                    this.categories = [];
                    this.detections = [];
                    this.results = [];
                    this.displayedResults = [];
                    this.selectedRaceId = null;
                    this.selectedCheckpointId = null;
                    this.liveResults = [];
                    this.selectedResult = null;
                    this.runnerCheckpoints = [];
                    this.runnerAverageSpeed = null;
                    this.raceChrono = '00:00:00';
                    this.alertMessage = null;

                    // Clear filters
                    this.searchQuery = '';
                    this.categoryFilter = '';
                    this.sasFilter = '';
                    this.raceFilter = '';
                    this.checkpointFilter = '';
                    this.lapFilter = '';

                    // Clear editing states
                    this.editingField = null;
                    this.editingValue = '';
                    this.editingCheckpointId = null;

                    this.showToast('L\'événement a été archivé', 'info');
                }

                // If active event changed, reload
                if (response.data.length > 0 && response.data[0].id !== this.currentEventId) {
                    await this.loadEvent();
                    this.showToast('Événement mis à jour', 'info');
                }
            } catch (error) {
                console.error('Erreur vérification statut événement:', error);
            }
        },

        startAlertCheck() {
            // Check for late runners every minute
            setInterval(() => this.checkForLateRunners(), 60000);
            // Run once immediately after 10 seconds
            setTimeout(() => this.checkForLateRunners(), 10000);
        },

        checkForLateRunners() {
            if (!this.hasOngoingRaces() || this.readers.length === 0) {
                return;
            }

            // Get all entrants from results who have at least one detection
            const entrantIds = [...new Set(this.results.map(r => r.entrant_id).filter(Boolean))];

            let lateRunners = [];

            for (let entrantId of entrantIds) {
                const runnerResults = this.results.filter(r => r.entrant_id === entrantId);
                if (runnerResults.length === 0) continue;

                // Get the entrant info from the first result
                const entrant = runnerResults[0].entrant;
                if (!entrant) continue;

                // Sort results by reader distance
                const sortedResults = runnerResults
                    .filter(r => r.reader_id)
                    .map(r => {
                        const reader = this.readers.find(rd => rd.id === r.reader_id);
                        return {
                            ...r,
                            distance: reader ? reader.distance_from_start : 0
                        };
                    })
                    .sort((a, b) => a.distance - b.distance);

                if (sortedResults.length < 2) continue;

                // Calculate average speed from last two checkpoints
                const last = sortedResults[sortedResults.length - 1];
                const secondLast = sortedResults[sortedResults.length - 2];

                const distance = last.distance - secondLast.distance;
                const timeMs = new Date(last.raw_time) - new Date(secondLast.raw_time);
                const timeHours = timeMs / (1000 * 60 * 60);
                const avgSpeed = distance / timeHours; // km/h

                if (avgSpeed <= 0) continue;

                // Find next checkpoint after last detection
                const nextCheckpoint = this.readers
                    .filter(r => r.distance_from_start > last.distance)
                    .sort((a, b) => a.distance_from_start - b.distance_from_start)[0];

                if (!nextCheckpoint) continue;

                // Calculate expected arrival time at next checkpoint
                const distanceToNext = nextCheckpoint.distance_from_start - last.distance;
                const timeNeededHours = distanceToNext / avgSpeed;
                const timeNeededMs = timeNeededHours * 60 * 60 * 1000;
                const expectedArrival = new Date(new Date(last.raw_time).getTime() + timeNeededMs);

                // Check if runner is late
                const now = new Date();
                const delayMinutes = (now - expectedArrival) / (1000 * 60);

                if (delayMinutes > this.alertThreshold) {
                    const severity = delayMinutes > 15 ? 'critical' : 'warning';
                    lateRunners.push({
                        entrant,
                        checkpoint: nextCheckpoint.location,
                        delayMinutes: Math.floor(delayMinutes),
                        severity
                    });
                }
            }

            // Update alert message
            if (lateRunners.length > 0) {
                const critical = lateRunners.filter(r => r.severity === 'critical');
                const warning = lateRunners.filter(r => r.severity === 'warning');

                let message = '';
                if (critical.length > 0) {
                    const runner = critical[0];
                    message = `🚨 CRITIQUE: Dossard #${runner.entrant.bib_number} (${runner.entrant.firstname} ${runner.entrant.lastname}) devrait être à ${runner.checkpoint} (retard ${runner.delayMinutes} min)`;
                } else if (warning.length > 0) {
                    const runner = warning[0];
                    message = `⚠️ Attention: Dossard #${runner.entrant.bib_number} (${runner.entrant.firstname} ${runner.entrant.lastname}) devrait être à ${runner.checkpoint} (retard ${runner.delayMinutes} min)`;
                }

                this.alertMessage = message;
            }
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            setTimeout(() => this.toastMessage = null, 3000);
        },

        formatTime(datetime) {
            if (!datetime) return '-';
            return new Date(datetime).toLocaleTimeString('fr-FR');
        },

        formatDuration(seconds) {
            if (!seconds || seconds === 0) return '-';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        },

        // Manual timing functions
        addManualTimestamp() {
            // Get current time in local timezone (not UTC)
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timestamp = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

            this.manualTimestamps.push({
                timestamp: timestamp,
                time: new Date().toLocaleTimeString('fr-FR')
            });
            this.saveManualTimestampsToStorage();
            this.showToast(`Temps ${this.manualTimestamps.length} enregistré`, 'success');
        },

        loadManualTimestampsFromStorage() {
            const stored = localStorage.getItem(`chronofront_manual_times_${this.currentEventId}`);
            if (stored) {
                this.manualTimestamps = JSON.parse(stored);
            }
        },

        saveManualTimestampsToStorage() {
            localStorage.setItem(`chronofront_manual_times_${this.currentEventId}`, JSON.stringify(this.manualTimestamps));
        },

        clearManualTimestamps() {
            if (!confirm(`Supprimer ${this.manualTimestamps.length} temps enregistrés ?`)) return;
            this.manualTimestamps = [];
            this.saveManualTimestampsToStorage();
            this.showManualTimesModal = false;
        },

        quickClearManualTimestamps() {
            // Quick clear without confirmation for rapid correction
            const count = this.manualTimestamps.length;
            this.manualTimestamps = [];
            this.saveManualTimestampsToStorage();
            this.showToast(`${count} temps effacés`, 'success');
        },

        removeManualTimestamp(index) {
            this.manualTimestamps.splice(index, 1);
            this.saveManualTimestampsToStorage();
        },

        startEditingTimestamp(index) {
            this.editingTimestampIndex = index;
            // Extract time from display format (HH:MM:SS)
            const timestamp = this.manualTimestamps[index];
            this.editingTimestampValue = timestamp.time;
        },

        saveEditedTimestamp(index) {
            if (this.editingTimestampValue) {
                // Convert edited time back to full timestamp
                const timestamp = this.manualTimestamps[index];
                const date = new Date(timestamp.timestamp);

                // Parse the edited time (format: HH:MM:SS)
                const timeParts = this.editingTimestampValue.split(':');
                if (timeParts.length >= 2) {
                    date.setHours(parseInt(timeParts[0]));
                    date.setMinutes(parseInt(timeParts[1]));
                    date.setSeconds(timeParts[2] ? parseInt(timeParts[2]) : 0);

                    // Update timestamp
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const seconds = String(date.getSeconds()).padStart(2, '0');

                    this.manualTimestamps[index].timestamp = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                    this.manualTimestamps[index].time = `${hours}:${minutes}:${seconds}`;

                    this.saveManualTimestampsToStorage();
                    this.showToast('Temps modifié', 'success');
                }
            }
            this.editingTimestampIndex = null;
            this.editingTimestampValue = '';
        },

        cancelEditingTimestamp() {
            this.editingTimestampIndex = null;
            this.editingTimestampValue = '';
        },

        saveManualCheckpointToStorage() {
            if (this.manualCheckpointId && this.currentEventId) {
                localStorage.setItem(`chronofront_manual_checkpoint_${this.currentEventId}`, this.manualCheckpointId);
            }
        },

        loadManualCheckpointFromStorage() {
            if (!this.currentEventId) {
                return;
            }

            const key = `chronofront_manual_checkpoint_${this.currentEventId}`;
            const stored = localStorage.getItem(key);

            if (stored) {
                this.manualCheckpointId = stored;
            }
        },

        async importManualTimesQuick() {
            if (!this.manualCheckpointId) {
                alert('Veuillez sélectionner un point de passage');
                return;
            }

            const validBibs = this.manualBibs.filter(b => b && b.trim());
            if (validBibs.length !== this.manualTimestamps.length) {
                alert(`Veuillez saisir les ${this.manualTimestamps.length} dossards`);
                return;
            }

            this.importingManualTimes = true;

            try {
                // Special case: ABD (Abandon)
                if (this.manualCheckpointId === 'ABD') {
                    const response = await axios.post('/results/mark-abd', {
                        event_id: this.currentEventId,
                        bib_numbers: validBibs
                    });

                    let message = `${response.data.updated + response.data.created} coureur(s) marqué(s) comme ABD`;
                    if (response.data.created > 0) {
                        message += `\n(${response.data.created} nouveaux résultats créés)`;
                    }
                    if (response.data.not_found_count > 0) {
                        message += `\n${response.data.not_found_count} dossard(s) non trouvé(s): ${response.data.not_found.join(', ')}`;
                    }
                    if (response.data.error_count > 0) {
                        message += `\n${response.data.error_count} erreur(s): ${response.data.errors.join(', ')}`;
                    }

                    alert(message);
                    this.showToast(`${response.data.updated + response.data.created} ABD enregistré(s)`, 'success');

                    // Reset
                    this.manualTimestamps = [];
                    this.manualBibs = [];
                    this.csvFile = null;
                    this.saveManualTimestampsToStorage();
                    this.showManualTimesModal = false;
                    await this.loadAllResults();
                    return;
                }

                // Normal case: add times
                const times = this.manualTimestamps.map((t, index) => ({
                    timestamp: t.timestamp,
                    bib_number: this.manualBibs[index].trim(),
                    reader_id: this.manualCheckpointId
                }));

                const response = await axios.post('/results/manual-batch', {
                    event_id: this.currentEventId,
                    reader_id: this.manualCheckpointId,
                    times: times
                });

                this.showToast(`${response.data.created} temps ajoutés avec succès`, 'success');

                if (response.data.errors > 0) {
                    console.warn('Erreurs:', response.data.error_details);
                    alert(`Attention: ${response.data.errors} dossards non trouvés`);
                }

                // Reset
                this.manualTimestamps = [];
                this.manualBibs = [];
                this.csvFile = null;
                this.saveManualTimestampsToStorage();
                this.showManualTimesModal = false;
                await this.loadAllResults();

            } catch (error) {
                console.error('Erreur import:', error);
                alert('Erreur lors de l\'import: ' + (error.response?.data?.message || error.message));
            } finally {
                this.importingManualTimes = false;
            }
        },

        async importManualTimesFromCSV() {
            if (!this.csvFile) {
                alert('Veuillez sélectionner un fichier CSV');
                return;
            }

            if (!this.manualCheckpointId) {
                alert('Veuillez sélectionner un point de passage');
                return;
            }

            this.importingManualTimes = true;

            try {
                const text = await this.csvFile.text();
                const lines = text.trim().split('\n').map(l => l.trim()).filter(l => l);

                // Special case: ABD (Abandon)
                if (this.manualCheckpointId === 'ABD') {
                    const response = await axios.post('/results/mark-abd', {
                        event_id: this.currentEventId,
                        bib_numbers: lines
                    });

                    let message = `${response.data.updated + response.data.created} coureur(s) marqué(s) comme ABD`;
                    if (response.data.created > 0) {
                        message += `\n(${response.data.created} nouveaux résultats créés)`;
                    }
                    if (response.data.not_found_count > 0) {
                        message += `\n${response.data.not_found_count} dossard(s) non trouvé(s): ${response.data.not_found.join(', ')}`;
                    }
                    if (response.data.error_count > 0) {
                        message += `\n${response.data.error_count} erreur(s): ${response.data.errors.join(', ')}`;
                    }

                    alert(message);
                    this.showToast(`${response.data.updated + response.data.created} ABD enregistré(s)`, 'success');

                    // Reset
                    this.manualTimestamps = [];
                    this.manualBibs = [];
                    this.csvFile = null;
                    this.saveManualTimestampsToStorage();
                    this.showManualTimesModal = false;
                    await this.loadAllResults();
                    return;
                }

                // Normal case: match bibs with times
                if (lines.length !== this.manualTimestamps.length) {
                    alert(`Erreur : ${lines.length} dossards dans le CSV mais ${this.manualTimestamps.length} temps enregistrés`);
                    this.importingManualTimes = false;
                    return;
                }

                const times = this.manualTimestamps.map((t, index) => ({
                    timestamp: t.timestamp,
                    bib_number: lines[index],
                    reader_id: this.manualCheckpointId
                }));

                const response = await axios.post('/results/manual-batch', {
                    event_id: this.currentEventId,
                    reader_id: this.manualCheckpointId,
                    times: times
                });

                this.showToast(`${response.data.created} temps ajoutés avec succès`, 'success');

                if (response.data.errors > 0) {
                    console.warn('Erreurs:', response.data.error_details);
                    alert(`Attention: ${response.data.errors} dossards non trouvés`);
                }

                // Reset
                this.manualTimestamps = [];
                this.manualBibs = [];
                this.csvFile = null;
                this.saveManualTimestampsToStorage();
                this.showManualTimesModal = false;
                await this.loadAllResults();

            } catch (error) {
                console.error('Erreur import:', error);
                alert('Erreur lors de l\'import: ' + (error.response?.data?.message || error.message));
            } finally {
                this.importingManualTimes = false;
            }
        },

        // Single manual entry functions
        setSingleEntryTimeNow() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            this.singleEntry.time = `${hours}:${minutes}:${seconds}`;
        },

        async addSingleManualEntry() {
            if (!this.singleEntry.bibNumber || !this.singleEntry.checkpointId) {
                return;
            }

            this.addingSingleEntry = true;

            try {
                const payload = {
                    event_id: this.currentEventId,
                    bib_number: this.singleEntry.bibNumber,
                    reader_id: this.singleEntry.checkpointId,
                    status: this.singleEntry.status
                };

                // Only add timestamp if status is normal and time is provided
                if (this.singleEntry.status === 'normal' && this.singleEntry.time) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    payload.raw_time = `${year}-${month}-${day} ${this.singleEntry.time}`;
                }

                const response = await axios.post('/results/manual-single', payload);

                if (response.data.success) {
                    const statusLabel = this.singleEntry.status === 'dns' ? 'Non partant' :
                                      this.singleEntry.status === 'dnf' ? 'ABD' : 'Temps ajouté';
                    this.showToast(`Dossard ${this.singleEntry.bibNumber}: ${statusLabel}`, 'success');

                    // Reset form
                    this.singleEntry = {
                        bibNumber: '',
                        checkpointId: '',
                        status: 'normal',
                        time: ''
                    };

                    await this.loadAllResults();
                    this.showManualTimesModal = false;
                } else {
                    alert(response.data.message || 'Erreur lors de l\'ajout');
                }

            } catch (error) {
                console.error('Erreur ajout:', error);
                alert('Erreur: ' + (error.response?.data?.message || error.message));
            } finally {
                this.addingSingleEntry = false;
            }
        },

        // Runner status management
        async updateRunnerStatus(result, status) {
            if (!result || !result.id) return;

            try {
                const response = await axios.post(`/results/${result.id}/status`, {
                    status: status
                });

                if (response.data.success) {
                    const statusLabel = status === 'dns' ? 'Non partant' :
                                      status === 'dnf' ? 'ABD' : 'Actif';
                    this.showToast(`Statut mis à jour: ${statusLabel}`, 'success');
                    await this.loadAllResults();
                } else {
                    alert(response.data.message || 'Erreur lors de la mise à jour');
                }

            } catch (error) {
                console.error('Erreur mise à jour statut:', error);
                alert('Erreur: ' + (error.response?.data?.message || error.message));
            }
        },

        getRunnerStatusValue(result) {
            if (!result || !result.status) return 'active';
            // Map DB status to frontend values
            const statusMap = {
                'V': 'active',
                'DNS': 'dns',
                'DNF': 'dnf'
            };
            return statusMap[result.status] || 'active';
        },

        getDisplayTime(result) {
            if (!result) return '-';
            // Show status text instead of time for DNS/DNF
            if (result.status === 'DNS') return 'Non partant';
            if (result.status === 'DNF') return 'ABD';

            // For multi-lap races (n_laps, infinite_loop), show lap time instead of cumulative time
            const raceType = result.race?.type;
            if (raceType === 'n_laps' || raceType === 'infinite_loop') {
                return this.formatDuration(result.lap_time);
            }

            // For single-passage races, show formatted time (cumulative)
            return result.formatted_time || '-';
        },

        getLapSpeed(result) {
            if (!result || !result.race) return '-';

            const raceType = result.race.type;
            const distance = result.race.distance;

            // For multi-lap races (n_laps, infinite_loop), show LAP SPEED (vitesse du tour)
            if ((raceType === 'n_laps' || raceType === 'infinite_loop') && result.lap_time && distance > 0) {
                const hours = result.lap_time / 3600;
                const lapSpeed = (distance / hours).toFixed(2);
                return lapSpeed + ' km/h';
            }

            // For single-passage races, show cumulative speed
            return result.speed ? result.speed + ' km/h' : '-';
        },

        // Intermediate time management functions
        setIntermediateTimeNow() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            // Format for separate date and time inputs
            this.intermediateDate = `${year}-${month}-${day}`;
            this.intermediateTime = `${hours}:${minutes}:${seconds}`;
        },

        async addIntermediateTime() {
            if (!this.selectedResult || !this.intermediateReaderId || !this.intermediateDate || !this.intermediateTime) {
                return;
            }

            this.saving = true;

            try {
                // Find the selected reader to get its location name
                const reader = this.readers.find(r => r.id == this.intermediateReaderId);
                if (!reader) {
                    throw new Error('Lecteur non trouvé');
                }

                // Build datetime string: YYYY-MM-DD HH:MM:SS
                const datetime = `${this.intermediateDate} ${this.intermediateTime}`;

                // Check if a result already exists for this runner at this checkpoint
                const existingResult = this.results.find(r =>
                    r.entrant_id === this.selectedResult.entrant_id &&
                    (r.reader_id == reader.id || r.reader_location === reader.location)
                );

                let response;
                let updatedResult;

                if (existingResult) {
                    // Update existing result
                    response = await axios.put(`/results/${existingResult.id}`, {
                        raw_time: datetime
                    });

                    // Update in local array
                    const resultIndex = this.results.findIndex(r => r.id === existingResult.id);
                    if (resultIndex !== -1) {
                        this.results[resultIndex] = response.data;
                    }
                    updatedResult = response.data;

                    this.showToast(`Temps modifié: ${reader.location}`, 'success');
                } else {
                    // Create new result
                    response = await axios.post('/results/time', {
                        entrant_id: this.selectedResult.entrant_id,
                        race_id: this.selectedResult.race_id,
                        reader_id: reader.id,
                        reader_location: reader.location,
                        raw_time: datetime,
                        is_manual: true
                    });

                    // Add to local array
                    updatedResult = response.data.result;
                    this.results.unshift(updatedResult);

                    this.showToast(`Temps ajouté: ${reader.location}`, 'success');
                }

                this.filterResults();

                // Reset form
                this.intermediateReaderId = '';
                this.intermediateDate = '';
                this.intermediateTime = '';

                // Update runner checkpoints
                this.calculateRunnerCheckpoints();

            } catch (error) {
                console.error('Erreur ajout/modification temps:', error);
                alert('Erreur: ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },

        async adjustResultTime(resultId, seconds) {
            if (!confirm(`Ajuster ce temps de ${seconds > 0 ? '+' : ''}${seconds} secondes ?`)) {
                return;
            }

            this.saving = true;

            try {
                // Find the result in our data
                const resultIndex = this.results.findIndex(r => r.id === resultId);
                if (resultIndex === -1) {
                    throw new Error('Résultat non trouvé');
                }

                const result = this.results[resultIndex];

                // Calculate new time
                const currentTime = new Date(result.raw_time);
                currentTime.setSeconds(currentTime.getSeconds() + seconds);

                const year = currentTime.getFullYear();
                const month = String(currentTime.getMonth() + 1).padStart(2, '0');
                const day = String(currentTime.getDate()).padStart(2, '0');
                const hours = String(currentTime.getHours()).padStart(2, '0');
                const minutes = String(currentTime.getMinutes()).padStart(2, '0');
                const secs = String(currentTime.getSeconds()).padStart(2, '0');
                const newTime = `${year}-${month}-${day} ${hours}:${minutes}:${secs}`;

                const response = await axios.put(`/results/${resultId}`, {
                    raw_time: newTime
                });

                // Update local result with response data (includes recalculated fields)
                this.results[resultIndex] = response.data;
                this.filterResults();

                this.showToast(`Temps ajusté de ${seconds > 0 ? '+' : ''}${seconds}s`, 'success');

                // Update runner checkpoints
                this.calculateRunnerCheckpoints();

            } catch (error) {
                console.error('Erreur ajustement:', error);
                alert('Erreur: ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },

        async deleteResult(resultId) {
            if (!confirm('Supprimer définitivement ce passage ?')) {
                return;
            }

            this.saving = true;

            try {
                await axios.delete(`/results/${resultId}`);

                // Remove from local results array
                const resultIndex = this.results.findIndex(r => r.id === resultId);
                if (resultIndex !== -1) {
                    this.results.splice(resultIndex, 1);
                    this.filterResults();
                }

                this.showToast('Passage supprimé', 'success');

                // Update runner checkpoints
                this.calculateRunnerCheckpoints();

            } catch (error) {
                console.error('Erreur suppression:', error);
                alert('Erreur: ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },

        // RFID File Import Functions
        closeRfidModal() {
            this.showRfidFileModal = false;
            this.rfidFile = null;
            this.rfidCheckpointId = '';
            this.rfidImportResults = null;
        },

        async importRfidFile() {
            if (!this.rfidFile || !this.rfidCheckpointId) {
                return;
            }

            this.importingRfidFile = true;

            try {
                // Find the selected reader
                const reader = this.readers.find(r => r.id == this.rfidCheckpointId);
                if (!reader) {
                    throw new Error('Lecteur non trouvé');
                }

                // Read file content
                const text = await this.rfidFile.text();
                const lines = text.trim().split('\n').map(l => l.trim()).filter(l => l);

                // Parse all lines into detections array
                const detections = [];
                const parseErrors = [];

                for (const line of lines) {
                    // Parse format: [TAG]:aYYYYMMDDHHMMSSmm
                    const match = line.match(/\[(\d+)\]:a(\d{8})(\d{6})\d+/);

                    if (!match) {
                        parseErrors.push({ line, error: 'Format invalide' });
                        continue;
                    }

                    const rfidTag = match[1];
                    const dateStr = match[2]; // YYYYMMDD
                    const timeStr = match[3]; // HHMMSS

                    // Build datetime: YYYY-MM-DD HH:MM:SS
                    const year = dateStr.substring(0, 4);
                    const month = dateStr.substring(4, 6);
                    const day = dateStr.substring(6, 8);
                    const hours = timeStr.substring(0, 2);
                    const minutes = timeStr.substring(2, 4);
                    const seconds = timeStr.substring(4, 6);

                    const datetime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

                    detections.push({
                        rfid_tag: rfidTag,
                        timestamp: datetime
                    });
                }

                if (parseErrors.length > 0) {
                    console.warn('Erreurs de parsing:', parseErrors);
                }

                // Send all detections in one batch request
                const response = await axios.post('/results/rfid-batch', {
                    race_id: this.selectedRaceId || undefined,
                    reader_id: reader.id,
                    detections: detections
                });

                // Show results
                this.rfidImportResults = {
                    total: lines.length,
                    success: response.data.created + response.data.updated,
                    errors: response.data.errors + parseErrors.length,
                    created: response.data.created,
                    updated: response.data.updated
                };

                if (response.data.errors > 0 || parseErrors.length > 0) {
                    console.warn('Erreurs d\'import RFID:', response.data.error_details, parseErrors);
                }

                // Reload results to show new/updated detections
                await this.loadAllResults();

                const message = `Import terminé: ${response.data.created} créées, ${response.data.updated} mises à jour`;
                this.showToast(message, response.data.success ? 'success' : 'error');

            } catch (error) {
                console.error('Erreur import RFID:', error);
                alert('Erreur lors de l\'import: ' + (error.response?.data?.message || error.message));
                this.rfidImportResults = {
                    total: 0,
                    success: 0,
                    errors: 1
                };
            } finally {
                this.importingRfidFile = false;
            }
        },

        // Delete single detection
        async deleteDetection(result) {
            if (!confirm(`Supprimer la détection du coureur #${result.entrant?.bib_number} ?\n\nCette action est irréversible.`)) {
                return;
            }

            try {
                await axios.delete(`/results/${result.id}`);
                this.showToast('Détection supprimée', 'success');

                // Close panel and reload
                this.selectedResult = null;
                await this.loadAllResults();
            } catch (error) {
                console.error('Erreur suppression:', error);
                this.showToast('Erreur lors de la suppression', 'error');
            }
        },

        // Clear all results
        async confirmClearAllArrivals() {
            if (!confirm('ATTENTION: Ceci va VIDER TOUTE LA TABLE RESULTS !\n\nCette action est IRRÉVERSIBLE.\n\nContinuer ?')) {
                return;
            }

            try {
                const response = await axios.post('/results/clear-arrivals');

                this.showToast(response.data.message || 'Table results vidée', 'success');

                // Reload results
                await this.loadAllResults();
            } catch (error) {
                console.error('Erreur lors de la suppression des arrivées:', error);
                console.error('Error response:', error.response?.data);

                let errorMsg = 'Erreur lors de la suppression des heures d\'arrivée';
                if (error.response?.data?.message) {
                    errorMsg += ': ' + error.response.data.message;
                }
                if (error.response?.data?.debug) {
                    console.error('Debug info:', error.response.data.debug);
                }

                this.showToast(errorMsg, 'error');
            }
        }
    }
}
</script>
@endsection
