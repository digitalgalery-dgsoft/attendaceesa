@extends('portal.layout')

@section('title', 'Detail Laporan ' . $submission->submission_code . ' - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Detail Dokumen Laporan')
@section('breadcrumb_active', $submission->submission_code)

@push('styles')
<style>
    .report-view-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        font-family: 'Outfit', sans-serif;
    }

    /* FLASH ALERT */
    .alert-banner {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        animation: fadeIn 0.3s ease;
    }
    .alert-success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }
    .alert-danger {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    /* BANNER HEADER CARD */
    .report-banner-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.25rem;
    }

    .banner-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .banner-icon-box {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(15, 82, 186, 0.12) 0%, rgba(37, 99, 235, 0.08) 100%);
        color: #0F52BA;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
        border: 1px solid rgba(15, 82, 186, 0.2);
    }

    .banner-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        letter-spacing: -0.3px;
        line-height: 1.25;
        margin-bottom: 4px;
    }

    .banner-subtitle {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.85rem;
        color: var(--text-muted);
        flex-wrap: wrap;
    }

    .code-badge {
        font-family: monospace;
        font-weight: 700;
        background: rgba(15, 82, 186, 0.08);
        color: #0F52BA;
        padding: 2px 8px;
        border-radius: 6px;
        border: 1px solid rgba(15, 82, 186, 0.2);
        font-size: 0.82rem;
    }

    .banner-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 700;
        border: 1px solid;
        letter-spacing: 0.2px;
    }

    /* APPROVAL ACTION BUTTONS */
    .btn-action-approve {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        background: #16a34a;
        color: #ffffff;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }
    .btn-action-approve:hover {
        background: #15803d;
        transform: translateY(-1px);
    }

    .btn-action-reject {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        background: #dc2626;
        color: #ffffff;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        transition: all 0.2s ease;
    }
    .btn-action-reject:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .btn-portal-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        background: #f1f5f9;
        color: var(--text-heading);
        border: 1px solid var(--border-color);
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .btn-portal-back:hover {
        background: #e2e8f0;
    }

    /* 2-COLUMN OVERVIEW GRID */
    .overview-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    @media (max-width: 900px) {
        .overview-grid {
            grid-template-columns: 1fr;
        }
    }

    .overview-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.35rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .overview-card-title {
        font-size: 0.82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.88rem;
    }
    .info-label {
        color: var(--text-muted);
        font-weight: 600;
        min-width: 130px;
        font-size: 0.82rem;
    }
    .info-value {
        color: var(--text-heading);
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    /* SPLIT CONTENT GRID (FORM DATA + PHOTO GALLERY) */
    .content-split-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 1.5rem;
        align-items: start;
    }
    .content-split-grid.no-media {
        grid-template-columns: 1fr;
    }
    @media (max-width: 992px) {
        .content-split-grid {
            grid-template-columns: 1fr;
        }
    }

    .panel-container {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .panel-header {
        padding: 1.15rem 1.35rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .panel-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .panel-count-badge {
        font-size: 0.74rem;
        font-weight: 700;
        background: rgba(15, 82, 186, 0.08);
        color: #0F52BA;
        padding: 3px 10px;
        border-radius: 999px;
    }

    /* PARAMETER TABLE */
    .param-table {
        width: 100%;
        border-collapse: collapse;
    }
    .param-table tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }
    .param-table tr:last-child {
        border-bottom: none;
    }
    .param-table tr:hover {
        background: #f8fafc;
    }
    .param-table td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
    }

    .param-num-col {
        width: 44px;
        text-align: center;
        padding-right: 0 !important;
    }
    .param-num-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 800;
        border: 1px solid #e2e8f0;
    }

    .param-label-col {
        padding-left: 0.85rem !important;
    }
    .param-label-text {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.35;
    }

    .param-val-col {
        text-align: right;
    }
    .val-currency {
        font-family: monospace;
        font-size: 0.95rem;
        font-weight: 800;
        color: #15803d;
        background: #dcfce7;
        padding: 4px 10px;
        border-radius: 8px;
        border: 1px solid #86efac;
        display: inline-block;
    }
    .val-number {
        font-weight: 800;
        font-size: 0.95rem;
        color: var(--text-heading);
        background: #f1f5f9;
        padding: 3px 10px;
        border-radius: 6px;
        display: inline-block;
    }
    .val-text {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-heading);
        line-height: 1.4;
        word-break: break-word;
    }
    .val-chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: flex-end;
    }
    .val-chip {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-heading);
    }
    .val-empty {
        color: #cbd5e1;
        font-style: italic;
    }

    /* MEDIA GALLERY */
    .media-gallery-grid {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .media-item-card {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .media-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .media-badge-tag {
        font-size: 0.74rem;
        font-weight: 700;
        color: #0F52BA;
        background: rgba(15, 82, 186, 0.1);
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .media-field-title {
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-heading);
        text-align: right;
        flex: 1;
    }

    .media-photo-frame {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .media-photo-frame img {
        width: 100%;
        max-height: 360px;
        object-fit: contain;
        transition: transform 0.2s ease;
    }
    .media-photo-frame img:hover {
        transform: scale(1.03);
    }

    .media-footer-bar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
    .media-full-btn {
        font-size: 0.78rem;
        font-weight: 700;
        color: #0F52BA;
        background: none;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 6px;
        transition: background 0.15s ease;
    }
    .media-full-btn:hover {
        background: rgba(15, 82, 186, 0.08);
        text-decoration: underline;
    }

    /* LIGHTBOX MODAL */
    .lightbox-backdrop {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 1.5rem;
    }
    .lightbox-content-box {
        position: relative;
        max-width: 92vw;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: zoomIn 0.2s ease;
    }
    @keyframes zoomIn {
        from { transform: scale(0.92); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .lightbox-header {
        padding: 1rem 1.35rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .lightbox-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lightbox-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .lightbox-action-btn {
        background: #f1f5f9;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-heading);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: background 0.15s ease;
    }
    .lightbox-action-btn:hover {
        background: #e2e8f0;
    }
    .lightbox-close-btn {
        background: none;
        border: none;
        font-size: 1.4rem;
        color: var(--text-muted);
        cursor: pointer;
        line-height: 1;
        padding: 0 4px;
        transition: color 0.15s ease;
    }
    .lightbox-close-btn:hover {
        color: #ef4444;
    }
    .lightbox-image-wrap {
        padding: 1rem;
        background: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        max-height: calc(92vh - 70px);
    }
    .lightbox-image-wrap img {
        max-width: 100%;
        max-height: 75vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    /* REJECT MODAL STYLING */
    .custom-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .custom-modal-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.75rem;
        max-width: 480px;
        width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
        animation: scaleUp 0.2s ease;
    }
    @keyframes scaleUp {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    /* OFFTAKE SUMMARY & PRODUCT BREAKDOWN STYLING */
    .offtake-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .offtake-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        overflow: hidden;
    }
    .offtake-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .offtake-stat-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .offtake-stat-label {
        font-size: 0.74rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .offtake-stat-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .offtake-stat-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-breakdown-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem 1.15rem;
        margin-bottom: 0.85rem;
        transition: all 0.15s ease;
    }
    .product-breakdown-card:hover {
        border-color: #cbd5e1;
        box-shadow: var(--shadow-sm);
    }

    /* CUSTOMER DATABASE SUMMARY & DETAIL STYLING */
    .cust-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .cust-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        overflow: hidden;
    }
    .cust-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .cust-stat-icon.blue { background: rgba(15, 82, 186, 0.12); color: #0F52BA; }
    .cust-stat-icon.emerald { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    .cust-stat-icon.indigo { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
    .cust-stat-icon.purple { background: rgba(168, 85, 247, 0.12); color: #a855f7; }
    .cust-stat-icon.gold { background: rgba(245, 158, 11, 0.12); color: #d97706; }
    .cust-stat-icon.rose { background: rgba(244, 63, 94, 0.12); color: #f43f5e; }

    .cust-stat-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .cust-stat-label {
        font-size: 0.74rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .cust-stat-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .cust-stat-sub {
        font-size: 0.76rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* CUSTOMER DETAIL CARDS */
    .cust-detail-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .cust-card-section-title {
        font-size: 0.88rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0.9rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f1f5f9;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .cust-persona-box {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }
    .cust-avatar-circle {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: linear-gradient(135deg, #0F52BA 0%, #0284c7 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        font-weight: 800;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(15, 82, 186, 0.25);
    }
    .cust-persona-info {
        flex: 1;
        min-width: 0;
    }
    .cust-persona-name {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-heading);
    }
    .cust-contact-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 6px;
    }
    .cust-contact-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.86rem;
        color: #334155;
        font-weight: 600;
    }
    .btn-chat-wa {
        background: #25D366;
        color: #ffffff !important;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.74rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .btn-chat-wa:hover {
        background: #1ebc59;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(37, 211, 102, 0.35);
    }

    .cust-badge {
        font-size: 0.74rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .cust-badge.segment {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .cust-badge.loyalty-yes {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }
    .cust-badge.loyalty-no {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* CONVERSION ALERT */
    .conversion-alert {
        padding: 0.85rem 1rem;
        border-radius: 10px;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }
    .conversion-alert.success {
        background: #f0fdf4;
        border: 1px solid #86efac;
    }
    .conversion-alert.info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .conversion-alert.warning {
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }
    .conv-icon {
        font-size: 1.25rem;
        line-height: 1.2;
    }

    /* BRAND FLOW COMPARISON */
    .brand-flow-container {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .brand-flow-box {
        flex: 1;
        min-width: 200px;
        border-radius: 12px;
        padding: 0.9rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .brand-flow-box.sought {
        border-left: 4px solid #64748b;
    }
    .brand-flow-box.bought.dulux {
        border-left: 4px solid #0F52BA;
        background: linear-gradient(135deg, rgba(15, 82, 186, 0.04) 0%, rgba(2, 132, 199, 0.06) 100%);
        border-color: rgba(15, 82, 186, 0.2);
    }
    .brand-flow-box.bought.other {
        border-left: 4px solid #e11d48;
    }
    .flow-label {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 4px;
    }
    .flow-brand-name {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 2px;
    }
    .flow-sub {
        font-size: 0.74rem;
        color: #94a3b8;
    }
    .flow-arrow {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #0F52BA;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        flex-shrink: 0;
    }

    /* ATTRIBUTES GRID */
    .cust-attributes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.75rem;
    }
    .attr-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .attr-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .attr-val {
        font-size: 0.88rem;
        color: #1e293b;
        font-weight: 700;
    }
    .attr-val.highlight-blue {
        color: #0F52BA;
    }

    /* VALUE CARD */
    .value-card {
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        border: 1px solid #86efac;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .value-card-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .value-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #dcfce7;
        color: #15803d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .value-label {
        font-size: 0.74rem;
        font-weight: 700;
        color: #15803d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .value-amount {
        font-size: 1.45rem;
        font-weight: 900;
        color: #166534;
        line-height: 1.2;
    }
    .value-note {
        font-size: 0.75rem;
        color: #64748b;
    }
    .value-badge {
        background: #15803d;
        color: #ffffff;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.8rem;
    }

    /* NOTE CARD */
    .note-card {
        border-left: 4px solid #f59e0b;
    }
    .cust-note-box {
        position: relative;
        padding-left: 1.75rem;
    }
    .quote-icon {
        position: absolute;
        top: 0;
        left: 0;
        font-size: 1.1rem;
        color: #f59e0b;
        opacity: 0.6;
    }
    .cust-note-text {
        margin: 0;
        font-size: 0.9rem;
        color: #334155;
        font-style: italic;
        line-height: 1.5;
    }

    /* DAILY MAINTENANCE SUMMARY & DETAIL STYLING */
    .dm-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .dm-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        overflow: hidden;
    }
    .dm-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .dm-stat-icon.blue { background: rgba(15, 82, 186, 0.12); color: #0F52BA; }
    .dm-stat-icon.emerald { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    .dm-stat-icon.amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
    .dm-stat-icon.rose { background: rgba(244, 63, 94, 0.12); color: #f43f5e; }
    .dm-stat-icon.purple { background: rgba(168, 85, 247, 0.12); color: #a855f7; }

    .dm-stat-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .dm-stat-label {
        font-size: 0.74rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .dm-stat-value {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .dm-stat-sub {
        font-size: 0.76rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* DAILY MAINTENANCE MACHINE BANNER & CHECKLIST */
    .dm-machine-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .dm-machine-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        flex-wrap: wrap;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, rgba(15, 82, 186, 0.04) 0%, rgba(2, 132, 199, 0.07) 100%);
        border: 1px solid rgba(15, 82, 186, 0.15);
        border-radius: 12px;
        margin-bottom: 1.25rem;
    }
    .dm-machine-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .dm-machine-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0F52BA 0%, #0284c7 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        box-shadow: 0 4px 10px rgba(15, 82, 186, 0.25);
        flex-shrink: 0;
    }
    .dm-machine-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-heading);
        margin: 0 0 4px 0;
    }
    .dm-machine-sn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #0369a1;
        background: #e0f2fe;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .dm-health-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 800;
        border-width: 1px;
        border-style: solid;
    }

    .dm-checklist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .dm-item-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .dm-item-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .dm-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .dm-item-title-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dm-item-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .dm-item-label {
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }
    .dm-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 9px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 800;
        border-width: 1px;
        border-style: solid;
        align-self: flex-start;
    }
    .dm-item-desc {
        font-size: 0.78rem;
        color: #64748b;
        line-height: 1.4;
        margin-top: 6px;
    }

    .dm-conclusion-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #0F52BA;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
    }
    .dm-conclusion-title {
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .dm-conclusion-text {
        margin: 0;
        font-size: 0.92rem;
        color: #334155;
        line-height: 1.5;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
    @php
        $status = $submission->status ?? 'pending';
        $statusConfig = match ($status) {
            'approved', 'verified' => [
                'label' => 'Terverifikasi (Valid)',
                'bg' => '#dcfce7',
                'color' => '#15803d',
                'border' => '#86efac',
                'icon' => 'fa-circle-check',
            ],
            'rejected' => [
                'label' => 'Ditolak (Tidak Sesuai)',
                'bg' => '#fee2e2',
                'color' => '#b91c1c',
                'border' => '#fca5a5',
                'icon' => 'fa-circle-xmark',
            ],
            default => [
                'label' => 'Menunggu Verifikasi',
                'bg' => '#fef3c7',
                'color' => '#b45309',
                'border' => '#fde68a',
                'icon' => 'fa-clock',
            ],
        };

        $employee = $submission->employee;
        $workLocation = $submission->workLocation ?? $submission->itineraryItem;
        $storeName = $submission->workLocation?->name ?? $submission->itineraryItem?->destination ?? $submission->store_name ?? 'Kunjungan Toko';
        $coordinates = ($submission->latitude && $submission->longitude) ? "{$submission->latitude}, {$submission->longitude}" : null;
        $mapsUrl = $coordinates ? "https://www.google.com/maps?q={$submission->latitude},{$submission->longitude}" : null;

        // Cek apakah submission ini memiliki list kompetitor dinamis
        $hasDynamicCompetitors = false;
        foreach ($submission->values as $v) {
            $fn = strtolower((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : '')));
            if ($fn === 'data_kompetitor_list') {
                $compData = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($compData) && !empty($compData)) {
                    $hasDynamicCompetitors = true;
                    break;
                }
            }
        }

        // Cek apakah submission ini memiliki list item offtake multi-produk
        $hasDynamicOfftakeItems = false;
        $offtakeItemsList = [];
        $offtakeGlobalData = [
            'total_volume_liter' => 0,
            'total_nilai_sales_rp' => 0,
            'total_volume_unit' => 0,
            'jml_customer_masuk' => null,
            'jml_customer_beli_cat' => null,
            'jml_customer_beli_dulux' => null,
            'estimasi_market_share_persen' => null,
            'tipe_laporan_offtake' => 'Sale',
        ];

        // Cek apakah submission ini memiliki list item OOS multi-produk
        $hasDynamicOosItems = false;
        $oosItemsList = [];
        $oosGlobalData = [
            'total_sku_oos' => 0,
            'max_lama_oos' => 0,
            'total_saran_qty' => 0,
            'tipe_laporan_oos' => 'OOS',
        ];

        // Cek apakah submission ini memiliki list item Stock End multi-produk
        $hasDynamicStockItems = false;
        $stockItemsList = [];
        $stockGlobalData = [
            'total_sku_stock' => 0,
            'total_volume_liter' => 0,
            'total_qty_galon' => 0,
            'total_qty_pail' => 0,
            'kategori_tinter' => '-',
            'tipe_tinter_warna' => '-',
            'level_persentase_isi' => '-',
            'catatan_stok' => '-',
        ];

        // Cek apakah submission ini memiliki list item Penjualan Event MBR multi-produk
        $hasDynamicMbrSalesItems = false;
        $mbrSalesItemsList = [];
        $mbrGlobalData = [
            'total_value_penjualan_rp' => 0,
            'total_qty_penjualan' => 0,
            'total_bayar_di_booth_rp' => 0,
            'total_bayar_di_kasir_rp' => 0,
        ];

        foreach ($submission->values as $v) {
            $fn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
            if ($fn === 'offtake_items_json') {
                $raw = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($raw) && !empty($raw)) {
                    $hasDynamicOfftakeItems = true;
                    $offtakeItemsList = $raw;
                }
            } elseif ($fn === 'mbr_sales_items_json') {
                $rawMbr = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($rawMbr) && !empty($rawMbr)) {
                    $hasDynamicMbrSalesItems = true;
                    $mbrSalesItemsList = $rawMbr;
                }
            } elseif ($fn === 'total_value_penjualan_rp') {
                $mbrGlobalData['total_value_penjualan_rp'] = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
            } elseif ($fn === 'total_qty_penjualan') {
                $mbrGlobalData['total_qty_penjualan'] = (int)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
            } elseif ($fn === 'total_bayar_di_booth_rp') {
                $mbrGlobalData['total_bayar_di_booth_rp'] = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
            } elseif ($fn === 'total_bayar_di_kasir_rp') {
                $mbrGlobalData['total_bayar_di_kasir_rp'] = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
            } elseif ($fn === 'oos_items_json') {
                $rawOos = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($rawOos) && !empty($rawOos)) {
                    $hasDynamicOosItems = true;
                    $oosItemsList = $rawOos;
                }
            } elseif ($fn === 'stock_items_json') {
                $rawStock = is_array($v->value_json) ? $v->value_json : (is_string($v->value_text) ? json_decode($v->value_text, true) : null);
                if (is_array($rawStock) && !empty($rawStock)) {
                    $hasDynamicStockItems = true;
                    $stockItemsList = $rawStock;
                }
            } elseif ($fn === 'tipe_laporan_oos') {
                $oosGlobalData['tipe_laporan_oos'] = $v->value_text ?: 'OOS';
            } elseif ($fn === 'total_volume_liter') {
                $offtakeGlobalData['total_volume_liter'] = (float)($v->value_number ?? $v->value_text ?? 0);
            } elseif (in_array($fn, ['total_volume_stok_liter', 'total_volume_stok'])) {
                $stockGlobalData['total_volume_liter'] = (float)($v->value_number ?? $v->value_text ?? 0);
            } elseif (in_array($fn, ['kategori_tinter', 'kategori_tinter_warna'])) {
                $stockGlobalData['kategori_tinter'] = $v->value_text ?: '-';
            } elseif (in_array($fn, ['tipe_tinter_warna', 'warna_tinter', 'warna_tinter_mesin'])) {
                $stockGlobalData['tipe_tinter_warna'] = $v->value_text ?: '-';
            } elseif (in_array($fn, ['level_persentase_isi', 'level_isi_tinter', 'persentase_level_tinter'])) {
                $stockGlobalData['level_persentase_isi'] = $v->value_text ?: '-';
            } elseif (in_array($fn, ['catatan_stok', 'catatan_khusus_stok', 'catatan'])) {
                $stockGlobalData['catatan_stok'] = $v->value_text ?: '-';
            } elseif ($fn === 'total_nilai_sales_rp') {
                $offtakeGlobalData['total_nilai_sales_rp'] = (float)($v->value_number ?? preg_replace('/[^0-9]/', '', (string)$v->value_text) ?? 0);
            } elseif ($fn === 'total_volume_unit') {
                $offtakeGlobalData['total_volume_unit'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'jml_customer_masuk') {
                $offtakeGlobalData['jml_customer_masuk'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'jml_customer_beli_cat') {
                $offtakeGlobalData['jml_customer_beli_cat'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'jml_customer_beli_dulux') {
                $offtakeGlobalData['jml_customer_beli_dulux'] = (int)($v->value_number ?? $v->value_text ?? 0);
            } elseif ($fn === 'estimasi_market_share_persen') {
                $offtakeGlobalData['estimasi_market_share_persen'] = $v->value_text;
            } elseif ($fn === 'tipe_laporan_offtake') {
                $offtakeGlobalData['tipe_laporan_offtake'] = $v->value_text ?: 'Sale';
            }
        }

        if ($hasDynamicMbrSalesItems && !empty($mbrSalesItemsList)) {
            $calcQty = 0; $calcVal = 0; $calcBooth = 0; $calcKasir = 0;
            foreach ($mbrSalesItemsList as $it) {
                $q = (int)($it['qty'] ?? 0);
                $v = (float)($it['value_rp'] ?? ($q * (float)($it['store_price'] ?? 0)));
                $pt = strtolower($it['payment_type'] ?? 'booth');
                $calcQty += $q;
                $calcVal += $v;
                if (str_contains($pt, 'kasir')) {
                    $calcKasir += $v;
                } else {
                    $calcBooth += $v;
                }
            }
            if ($mbrGlobalData['total_qty_penjualan'] <= 0) $mbrGlobalData['total_qty_penjualan'] = $calcQty;
            if ($mbrGlobalData['total_value_penjualan_rp'] <= 0) $mbrGlobalData['total_value_penjualan_rp'] = $calcVal;
            if ($mbrGlobalData['total_bayar_di_booth_rp'] <= 0 && $mbrGlobalData['total_bayar_di_kasir_rp'] <= 0) {
                $mbrGlobalData['total_bayar_di_booth_rp'] = $calcBooth;
                $mbrGlobalData['total_bayar_di_kasir_rp'] = $calcKasir;
            }
        }

        if ($hasDynamicOosItems && !empty($oosItemsList)) {
            $oosGlobalData['total_sku_oos'] = count($oosItemsList);
            $oosGlobalData['max_lama_oos'] = max(array_map(fn($it) => max(1, (int)($it['lama_oos_hari'] ?? 1)), $oosItemsList) ?: [1]);
            $oosGlobalData['total_saran_qty'] = array_sum(array_column($oosItemsList, 'saran_qty_order') ?: [0]);
        }

        if ($hasDynamicStockItems && !empty($stockItemsList)) {
            $stockGlobalData['total_sku_stock'] = count($stockItemsList);
            $calcGalon = 0; $calcPail = 0; $calcLiter = 0;
            foreach ($stockItemsList as $it) {
                $qG = (float)($it['stok_qty_galon'] ?? ($it['qty_galon'] ?? ($it['kuantiti_galon'] ?? 0)));
                $qP = (float)($it['stok_qty_pail'] ?? ($it['qty_pail'] ?? ($it['kuantiti_pail'] ?? 0)));
                $vL = (float)($it['total_volume_liter'] ?? (($qG * 2.5) + ($qP * 20.0)));
                $calcGalon += $qG;
                $calcPail += $qP;
                $calcLiter += $vL;
            }
            $stockGlobalData['total_qty_galon'] = $calcGalon;
            $stockGlobalData['total_qty_pail'] = $calcPail;
            if ($stockGlobalData['total_volume_liter'] <= 0 || $calcLiter > 0) {
                $stockGlobalData['total_volume_liter'] = $calcLiter;
            }
        }

        if (!$hasDynamicStockItems && ($template->code === 'RPT-DULUX-STOCK-END' || str_contains($template->code, 'STOCK-END'))) {
            $singleProd = '-';
            $singleBrand = 'Dulux';
            $singleBase = '-';
            $singleGalon = (float)($stockGlobalData['total_qty_galon'] ?? 0);
            $singlePail = (float)($stockGlobalData['total_qty_pail'] ?? 0);
            $singleVol = (float)($stockGlobalData['total_volume_liter'] ?? 0);

            foreach ($submission->values as $v) {
                $vFn = strtolower(trim((string)($v->field_name ?: ($v->formField ? $v->formField->field_name : ''))));
                if (in_array($vFn, ['produk_stock_end', 'produk', 'pilih_produk_dulux_catylac_yang_dicek'])) {
                    if (!empty($v->value_text)) $singleProd = $v->value_text;
                } elseif (in_array($vFn, ['brand', 'brand_cat'])) {
                    if (!empty($v->value_text)) $singleBrand = $v->value_text;
                } elseif (in_array($vFn, ['base_warna', 'warna', 'base_tipe_warna'])) {
                    if (!empty($v->value_text)) $singleBase = $v->value_text;
                } elseif (in_array($vFn, ['stok_qty_galon', 'kuantiti_galon'])) {
                    $singleGalon = (float)($v->value_number ?? $v->value_text ?? $singleGalon);
                } elseif (in_array($vFn, ['stok_qty_pail', 'kuantiti_pail'])) {
                    $singlePail = (float)($v->value_number ?? $v->value_text ?? $singlePail);
                } elseif (in_array($vFn, ['total_volume_stok_liter', 'total_volume_stok'])) {
                    $singleVol = (float)($v->value_number ?? $v->value_text ?? $singleVol);
                }
            }

            if ($singleVol > 0 || $singleGalon > 0 || $singlePail > 0) {
                $hasDynamicStockItems = true;
                if ($singleVol <= 0) {
                    $singleVol = ($singleGalon * 2.5) + ($singlePail * 20.0);
                }
                $stockGlobalData['total_sku_stock'] = 1;
                $stockGlobalData['total_volume_liter'] = $singleVol;
                $stockGlobalData['total_qty_galon'] = $singleGalon;
                $stockGlobalData['total_qty_pail'] = $singlePail;
                $stockItemsList = [
                    [
                        'brand' => $singleBrand ?: 'Dulux',
                        'product_name' => (!empty($singleProd) && $singleProd !== '-') ? $singleProd : 'Produk Stock End',
                        'warna' => $singleBase,
                        'qty_galon' => $singleGalon,
                        'qty_pail' => $singlePail,
                        'volume_liter' => $singleVol,
                        'stok_qty_galon' => $singleGalon,
                        'stok_qty_pail' => $singlePail,
                        'total_volume_liter' => $singleVol,
                    ]
                ];
            }
        }

        // Selalu prioritaskan kalkulasi akumulatif dari offtake_items_json jika tersedia
        if ($hasDynamicOfftakeItems && !empty($offtakeItemsList)) {
            $calcUnit = 0; $calcLiter = 0; $calcRp = 0;
            foreach ($offtakeItemsList as $it) {
                $qT = (float)($it['qty_tin'] ?? 0);
                $qG = (float)($it['qty_galon'] ?? 0);
                $qP = (float)($it['qty_pail'] ?? 0);
                $calcUnit += (int)($it['total_unit'] ?? ($qT + $qG + $qP));
                $calcLiter += (float)($it['total_liter'] ?? (($it['volume_tin_l'] ?? 0) + ($it['volume_galon_l'] ?? 0) + ($it['volume_pail_l'] ?? 0)));
                $calcRp += (float)($it['total_nilai_rp'] ?? 0);
            }
            if ($calcUnit > 0) $offtakeGlobalData['total_volume_unit'] = $calcUnit;
            if ($calcLiter > 0) $offtakeGlobalData['total_volume_liter'] = $calcLiter;
            if ($calcRp > 0) $offtakeGlobalData['total_nilai_sales_rp'] = $calcRp;

            // Perhitungan pintar market share jika belum tercatat atau 0%
            if (empty($offtakeGlobalData['estimasi_market_share_persen']) || $offtakeGlobalData['estimasi_market_share_persen'] === '0%' || $offtakeGlobalData['estimasi_market_share_persen'] === '0') {
                $cCat = (float)($offtakeGlobalData['jml_customer_beli_cat'] ?? 0);
                $cDulux = (float)($offtakeGlobalData['jml_customer_beli_dulux'] ?? 0);
                if ($cCat > 0) {
                    $ms = round(($cDulux / $cCat) * 100);
                    $offtakeGlobalData['estimasi_market_share_persen'] = "{$ms}%";
                } elseif ($cDulux > 0) {
                    $offtakeGlobalData['estimasi_market_share_persen'] = "100%";
                }
            }
        }

        $suppressCompetitorFields = [
            'merk_kompetitor',
            'subbrand_kompetitor',
            'harga_kompetitor_tin_rp',
            'harga_kompetitor_galon_rp',
            'harga_kompetitor_pail_rp',
            'merk_kompetitor_sejenis_di_toko',
            'nama_subbrand_kompetitor_yang_dicek',
            'harga_jual_kompetitor_kemasan_galon_2.5l/4-5kg_(rp)',
            'harga_jual_kompetitor_kemasan_pail_20l/25kg_(rp)',
            'harga_jual_kompetitor_kemasan_tin_/_kaleng_1l/1kg_(rp)',
        ];

        $suppressOfftakeFields = [
            'offtake_items_json',
            'sub_brand',
            'subbrand',
            'produk_terjual',
            'subbrand_produk',
            'brand',
            'brand_rm_base',
            'sub_brand1',
            'sub_brand2',
            'pilih_produk_sub_brand',
            'pilih_produk_/_sub_brand',
            'sub_brand_spesifik_/_varian_(sub_brand_1)',
            'detail_rm_/_base_(sub_brand_2)',
            'kemasan_tin',
            'kemasan_galon',
            'kemasan_pail',
            'qty_tin',
            'qty_galon',
            'qty_pail',
            'kuantiti_tin_terjual_(unit)',
            'kuantiti_galon_terjual_(unit)',
            'kuantiti_pail_terjual_(unit)',
            'volume_tin_l',
            'volume_galon_l',
            'volume_pail_l',
            'volume_tin_(liter)',
            'volume_galon_(liter)',
            'volume_pail_(liter)',
            'total_volume_unit',
            'total_volume_liter',
            'total_nilai_sales_rp',
            'grand_total_nilai_penjualan_(rupiah)',
            'grand_total_volume_penjualan_(liter)',
            'grand_total_kuantiti_unit_(tin_+_galon_+_pail)',
            'jml_customer_masuk',
            'jml_customer_beli_cat',
            'jml_customer_beli_dulux',
            'jumlah_customer_masuk',
            'jumlah_cust_yang_beli_cat',
            'jumlah_cust_yang_beli_produk_dulux',
            'estimasi_market_share_persen',
            'estimasi_market_share_(%)',
            'tipe_transaksi_hari_ini',
            'tipe_laporan_offtake',
        ];

        $suppressOosFields = [
            'oos_items_json',
            'tipe_laporan_oos',
            'produk_oos',
            'nama_produk_yang_kosong_oos',
            'pilih_produk_dulux_yang_mengalami_out_of_stock_oos',
            'kemasan_size_oos',
            'ukuran_kemasan_size',
            'kemasan_size_yang_kosong',
            'base_warna_oos',
            'base_tipe_warna',
            'base_kategori_warna_yang_kosong',
            'warna_ready_mix_oos',
            'lama_oos_hari',
            'lama_kondisi_barang_kosong_jumlah_hari',
            'lama_kondisi_oos_jumlah_hari',
            'saran_qty_order',
            'saran_kuantiti_order_ke_toko_qty_kemasan',
            'saran_kuantitas_order_qty_kaleng',
            'alasan_oos',
            'penyebab_alasan_out_of_stock_oos',
        ];

        $suppressStockFields = [
            'stock_items_json',
            'produk_stock_end',
            'nama_produk',
            'produk',
            'sub_brand',
            'subbrand',
            'brand',
            'kategori_produk',
            'kategori_cat',
            'stok_qty_galon',
            'stok_qty_pail',
            'qty_galon',
            'qty_pail',
            'kuantiti_galon',
            'kuantiti_pail',
            'base_warna',
            'base_cat',
            'total_volume_stok_liter',
            'total_volume_stok',
            'status_ketersediaan_tinter',
            'status_ketersediaan_tinter_di_toko',
            'status_tinter',
        ];

        // Cek apakah template ini merupakan Laporan Data Pelanggan & Konsumen Dulux
        $isCustomerDbReport = (
            str_contains($template->code, 'DATABASE-PELANGGAN') ||
            str_contains($template->code, 'DATA-PELANGGAN') ||
            str_contains($template->code, 'DATABASE_PELANGGAN') ||
            str_contains($template->code, 'DATA_PELANGGAN') ||
            str_contains(strtolower($template->title), 'data pelanggan') ||
            str_contains(strtolower($template->title), 'database pelanggan') ||
            str_contains(strtolower($template->title), 'konsumen')
        );

        $custValMap = [];
        if ($isCustomerDbReport) {
            foreach ($submission->values as $v) {
                $val = $v->value_text ?? ($v->value_number ?? $v->value_date ?? $v->value_json);
                if ($v->field_name) {
                    $custValMap[$v->field_name] = $val;
                    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->field_name), '_'));
                    $custValMap[$slug] = $val;
                }
                if ($v->formField) {
                    if ($v->formField->field_name) {
                        $custValMap[$v->formField->field_name] = $val;
                        $slugF = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->formField->field_name), '_'));
                        $custValMap[$slugF] = $val;
                    }
                    if ($v->formField->field_label) {
                        $slugL = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->formField->field_label), '_'));
                        $custValMap[$slugL] = $val;
                    }
                }
            }
        }

        $custNama = trim((string) ($custValMap['nama_lengkap_pelanggan'] ?? $custValMap['nama_pelanggan'] ?? $custValMap['nama_konsumen'] ?? $custValMap['nama'] ?? '-'));
        $custPhone = trim((string) ($custValMap['nomor_hp_whatsapp_pelanggan'] ?? $custValMap['no_hp_pelanggan'] ?? $custValMap['nomor_hp'] ?? $custValMap['no_hp'] ?? '-'));
        $custAlamat = trim((string) ($custValMap['alamat_domisili_pelanggan'] ?? $custValMap['alamat_pelanggan'] ?? $custValMap['alamat_konsumen'] ?? $custValMap['alamat'] ?? '-'));
        $custTipe = trim((string) ($custValMap['tipe_kategori_pelanggan'] ?? $custValMap['tipe_pelanggan'] ?? $custValMap['tipe_konsumen'] ?? 'Pemilik Rumah'));
        
        $custTujuan = trim((string) ($custValMap['tujuan_datang_ke_toko'] ?? $custValMap['tujuan_ke_toko'] ?? $custValMap['tujuan'] ?? 'Membeli Cat'));
        $custBrandDicari = trim((string) ($custValMap['brand_cat_yang_awalnya_dicari_ditanyakan'] ?? $custValMap['brand_dicari'] ?? $custValMap['brand_awalnya_dicari'] ?? '-'));
        $custBrandDibeli = trim((string) ($custValMap['brand_cat_yang_akhirnya_dibeli'] ?? $custValMap['brand_dibeli'] ?? $custValMap['brand_akhirnya_dibeli'] ?? '-'));
        $custAlasan = trim((string) ($custValMap['alasan_konsumen_memilih_brand_tersebut'] ?? $custValMap['alasan_pilih_brand'] ?? $custValMap['alasan_memilih'] ?? 'Rekomendasi DC'));
        
        $custTipePengecatan = trim((string) ($custValMap['tipe_pekerjaan_pengecatan'] ?? $custValMap['tipe_pengecatan'] ?? '-'));
        $custPreview = trim((string) ($custValMap['apakah_memerlukan_preview_warna_visualizer'] ?? $custValMap['memerlukan_preview'] ?? $custValMap['preview_warna'] ?? 'Tidak'));
        
        $rawValNum = $custValMap['estimasi_total_nilai_pembelian_rupiah'] ?? $custValMap['total_estimasi_nilai_pembelian_rupiah'] ?? $custValMap['value_pembelian_rp'] ?? $custValMap['value_pembelian'] ?? 0;
        $custNilaiBelanja = is_numeric($rawValNum) ? (float)$rawValNum : (float)preg_replace('/[^0-9.]/', '', (string)$rawValNum);
        
        $custLoyalty = trim((string) ($custValMap['program_mitra_dulux_painter_loyalty'] ?? $custValMap['painter_loyalty'] ?? $custValMap['program_mitra_dulux'] ?? 'Tidak Bersedia'));
        $custCatatan = trim((string) ($custValMap['catatan_khusus_keterangan'] ?? $custValMap['catatan_khusus_pelanggan'] ?? $custValMap['keterangan'] ?? $custValMap['catatan_pelanggan'] ?? ''));

        // Analisis Brand Switching & Loyalitas
        $isDuluxBought = (stripos($custBrandDibeli, 'dulux') !== false || stripos($custBrandDibeli, 'catylac') !== false || stripos($custBrandDibeli, 'aquashield') !== false);
        $isDuluxSought = (stripos($custBrandDicari, 'dulux') !== false || stripos($custBrandDicari, 'catylac') !== false || stripos($custBrandDicari, 'aquashield') !== false);
        $isBrandSwitch = ($isDuluxBought && !$isDuluxSought && !empty($custBrandDicari) && $custBrandDicari !== '-');
        $isLoyalDulux = ($isDuluxBought && $isDuluxSought);
        $isCompetitorBought = (!$isDuluxBought && !empty($custBrandDibeli) && $custBrandDibeli !== '-');

        // Link WhatsApp
        $cleanWa = preg_replace('/[^0-9]/', '', $custPhone);
        if (str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        } elseif (str_starts_with($cleanWa, '8')) {
            $cleanWa = '62' . $cleanWa;
        }
        $waLink = (strlen($cleanWa) >= 10) ? "https://wa.me/{$cleanWa}" : null;

        $suppressCustomerFields = [
            'nama_pelanggan',
            'nama_lengkap_pelanggan',
            'nama_konsumen',
            'nama',
            'no_hp_pelanggan',
            'nomor_hp_whatsapp_pelanggan',
            'nomor_hp_pelanggan',
            'no_hp',
            'alamat_pelanggan',
            'alamat_domisili_pelanggan',
            'alamat_konsumen',
            'alamat',
            'tipe_pelanggan',
            'tipe_kategori_pelanggan',
            'tipe_konsumen',
            'tujuan_ke_toko',
            'tujuan_datang_ke_toko',
            'brand_dicari',
            'brand_cat_yang_awalnya_dicari_ditanyakan',
            'brand_dibeli',
            'brand_cat_yang_akhirnya_dibeli',
            'alasan_pilih_brand',
            'alasan_konsumen_memilih_brand_tersebut',
            'tipe_pengecatan',
            'tipe_pekerjaan_pengecatan',
            'memerlukan_preview',
            'apakah_memerlukan_preview_warna_visualizer',
            'value_pembelian_rp',
            'estimasi_total_nilai_pembelian_rupiah',
            'total_estimasi_nilai_pembelian_rupiah',
            'painter_loyalty',
            'program_mitra_dulux_painter_loyalty',
            'keterangan',
            'catatan_khusus_keterangan',
            'catatan_khusus_pelanggan',
            'catatan_pelanggan',
            'foto_1',
            'foto_2',
            'foto_3',
            'foto_interaksi_pelanggan',
        ];

        // Cek apakah template ini merupakan Laporan Daily Maintenance Mesin Tinting
        $isDailyMaintenanceReport = (
            str_contains($template->code, 'DAILY-MAINTENANCE') ||
            str_contains($template->code, 'DAILY_MAINTENANCE') ||
            str_contains(strtolower($template->title), 'daily maintenance') ||
            str_contains(strtolower($template->title), 'maintenance mesin') ||
            str_contains(strtolower($template->title), 'perawatan mesin')
        );

        $dmValMap = [];
        if ($isDailyMaintenanceReport) {
            foreach ($submission->values as $v) {
                $val = $v->value_text ?? ($v->value_number ?? $v->value_date ?? $v->value_json);
                if ($v->field_name) {
                    $dmValMap[$v->field_name] = $val;
                    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->field_name), '_'));
                    $dmValMap[$slug] = $val;
                }
                if ($v->formField) {
                    if ($v->formField->field_name) {
                        $dmValMap[$v->formField->field_name] = $val;
                        $slugF = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->formField->field_name), '_'));
                        $dmValMap[$slugF] = $val;
                    }
                    if ($v->formField->field_label) {
                        $slugL = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $v->formField->field_label), '_'));
                        $dmValMap[$slugL] = $val;
                    }
                }
            }
        }

        $dmTipeMesin = trim((string) ($dmValMap['tipe_mesin_post'] ?? $dmValMap['tipe_mesin'] ?? $dmValMap['jenis_mesin'] ?? $dmValMap['tipe_mesin_tinting'] ?? '-'));
        $dmNoMesin = trim((string) ($dmValMap['no_mesin_post'] ?? $dmValMap['nomor_mesin_post'] ?? $dmValMap['no_mesin'] ?? $dmValMap['nomor_seri_mesin'] ?? '-'));
        $dmNozzle = trim((string) ($dmValMap['status_nozzle_cleaning'] ?? $dmValMap['nozzle_cleaning'] ?? $dmValMap['kebersihan_nozzle'] ?? $dmValMap['status_kebersihan_nozzle'] ?? '-'));
        $dmSirkulasi = trim((string) ($dmValMap['status_sirkulasi_tinter'] ?? $dmValMap['sirkulasi_tinter'] ?? $dmValMap['sirkulasi_pasta_tinter'] ?? '-'));
        $dmSoftware = trim((string) ($dmValMap['status_software_komputer'] ?? $dmValMap['software_komputer'] ?? $dmValMap['kondisi_komputer'] ?? '-'));
        $dmMix2win = trim((string) ($dmValMap['status_program_mix2win'] ?? $dmValMap['program_mix2win'] ?? $dmValMap['mix2win'] ?? $dmValMap['aplikasi_mix2win'] ?? '-'));
        $dmKesimpulan = trim((string) ($dmValMap['kesimpulan_maintenance'] ?? $dmValMap['kesimpulan'] ?? $dmValMap['catatan_maintenance'] ?? $dmValMap['keterangan'] ?? ''));

        $getStatusTone = function($str) {
            $s = strtolower(trim((string)$str));
            if (empty($s) || $s === '-') return ['type' => 'neutral', 'color' => '#64748b', 'bg' => '#f1f5f9', 'border' => '#e2e8f0', 'icon' => 'fa-circle-info'];
            if (str_contains($s, 'rusak') || str_contains($s, 'tersumbat') || str_contains($s, 'error') || str_contains($s, 'mati') || str_contains($s, 'macet') || str_contains($s, 'gagal') || str_contains($s, 'tidak normal')) {
                return ['type' => 'danger', 'color' => '#e11d48', 'bg' => '#ffe4e6', 'border' => '#fecdd3', 'icon' => 'fa-circle-xmark'];
            }
            if (str_contains($s, 'perlu') || str_contains($s, 'kotor') || str_contains($s, 'lambat') || str_contains($s, 'kurang') || str_contains($s, 'update') || str_contains($s, 'hang')) {
                return ['type' => 'warning', 'color' => '#d97706', 'bg' => '#fef3c7', 'border' => '#fde68a', 'icon' => 'fa-triangle-exclamation'];
            }
            if (str_contains($s, 'normal') || str_contains($s, 'bersih') || str_contains($s, 'baik') || str_contains($s, 'lancar') || str_contains($s, 'ready') || str_contains($s, 'ok') || str_contains($s, 'up to date') || str_contains($s, 'pembersihan') || str_contains($s, 'responsif')) {
                return ['type' => 'success', 'color' => '#15803d', 'bg' => '#dcfce7', 'border' => '#bbf7d0', 'icon' => 'fa-circle-check'];
            }
            return ['type' => 'info', 'color' => '#0369a1', 'bg' => '#e0f2fe', 'border' => '#bae6fd', 'icon' => 'fa-circle-check'];
        };

        $nozzleTone = $getStatusTone($dmNozzle);
        $sirkulasiTone = $getStatusTone($dmSirkulasi);
        $softwareTone = $getStatusTone($dmSoftware);
        $mix2winTone = $getStatusTone($dmMix2win);

        $hasDanger = ($nozzleTone['type'] === 'danger' || $sirkulasiTone['type'] === 'danger' || $softwareTone['type'] === 'danger' || $mix2winTone['type'] === 'danger');
        $hasWarning = ($nozzleTone['type'] === 'warning' || $sirkulasiTone['type'] === 'warning' || $softwareTone['type'] === 'warning' || $mix2winTone['type'] === 'warning');

        $machineHealth = [
            'status' => $hasDanger ? 'Kendala Teknis' : ($hasWarning ? 'Perlu Perhatian' : 'Kondisi Prima'),
            'badge_bg' => $hasDanger ? '#fee2e2' : ($hasWarning ? '#fef3c7' : '#dcfce7'),
            'badge_color' => $hasDanger ? '#b91c1c' : ($hasWarning ? '#b45309' : '#15803d'),
            'badge_border' => $hasDanger ? '#fca5a5' : ($hasWarning ? '#fcd34d' : '#86efac'),
            'icon' => $hasDanger ? 'fa-triangle-exclamation' : ($hasWarning ? 'fa-circle-exclamation' : 'fa-circle-check'),
            'desc' => $hasDanger ? 'Ditemukan kendala teknis pada mesin/komputer yang memerlukan tindak lanjut teknisi.' : ($hasWarning ? 'Terdapat catatan perawatan berkala yang perlu segera diselesaikan oleh promotor.' : 'Seluruh komponen mesin tinting dan sistem komputer dalam status optimal dan siap operasi.')
        ];

        $suppressDailyMaintenanceFields = [
            'tipe_mesin_post',
            'tipe_mesin',
            'jenis_mesin',
            'tipe_mesin_tinting',
            'no_mesin_post',
            'nomor_mesin_post',
            'no_mesin',
            'nomor_seri_mesin',
            'status_nozzle_cleaning',
            'nozzle_cleaning',
            'kebersihan_nozzle',
            'status_kebersihan_nozzle',
            'status_sirkulasi_tinter',
            'sirkulasi_tinter',
            'sirkulasi_pasta_tinter',
            'status_software_komputer',
            'software_komputer',
            'kondisi_komputer',
            'status_program_mix2win',
            'program_mix2win',
            'mix2win',
            'aplikasi_mix2win',
            'kesimpulan_maintenance',
            'kesimpulan',
            'catatan_maintenance',
            'keterangan',
            'foto_brush_cleaning',
            'foto_mesin_tinting',
            'foto_nozzle_cleaning',
            'foto_1',
            'foto_2',
            'foto_3',
        ];

        // Separate text inputs and photo/media attachments to prevent tall empty grid cards
        $textValues = $submission->values->filter(function($val) use ($hasDynamicCompetitors, $suppressCompetitorFields, $hasDynamicOfftakeItems, $suppressOfftakeFields, $hasDynamicOosItems, $suppressOosFields, $hasDynamicStockItems, $suppressStockFields, $isCustomerDbReport, $suppressCustomerFields, $isDailyMaintenanceReport, $suppressDailyMaintenanceFields, $hasDynamicMbrSalesItems) {
            $isMedia = in_array($val->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])
                || !empty($val->media_url)
                || !empty($val->file_path);
            if ($isMedia) return false;

            $fn = strtolower(trim((string)($val->field_name ?: ($val->formField ? $val->formField->field_name : ''))));
            $fl = strtolower(trim((string)($val->formField?->field_label ?? '')));
            $flClean = str_replace([' ', '-', '/'], '_', $fl);

            if ($fn === 'mbr_sales_items_json' || $fn === 'oos_items_json' || $fn === 'offtake_items_json' || $fn === 'stock_items_json' || $fn === 'status_ketersediaan_tinter' || $fn === 'status_ketersediaan_tinter_di_toko' || $flClean === 'status_ketersediaan_tinter_di_toko' || $flClean === 'status_ketersediaan_tinter') {
                return false;
            }

            if ($hasDynamicCompetitors) {
                if (in_array($fn, $suppressCompetitorFields) || in_array($flClean, $suppressCompetitorFields)) {
                    return false;
                }
            }

            if ($hasDynamicOfftakeItems) {
                if (in_array($fn, $suppressOfftakeFields) || in_array($flClean, $suppressOfftakeFields) || str_contains($fn, 'grand_total') || str_contains($flClean, 'grand_total')) {
                    return false;
                }
            }

            if ($hasDynamicOosItems) {
                if (in_array($fn, $suppressOosFields) || in_array($flClean, $suppressOosFields)) {
                    return false;
                }
            }

            if ($hasDynamicStockItems) {
                if (in_array($fn, $suppressStockFields) || in_array($flClean, $suppressStockFields)) {
                    return false;
                }
            }

            if ($hasDynamicMbrSalesItems) {
                $suppressMbrFields = [
                    'mbr_sales_items_json',
                    'total_qty_penjualan',
                    'total_value_penjualan_rp',
                    'total_bayar_di_booth_rp',
                    'total_bayar_di_kasir_rp',
                ];
                if (in_array($fn, $suppressMbrFields) || in_array($flClean, $suppressMbrFields)) {
                    return false;
                }
            }

            if ($isCustomerDbReport) {
                if (in_array($fn, $suppressCustomerFields) || in_array($flClean, $suppressCustomerFields)) {
                    return false;
                }
            }

            if ($isDailyMaintenanceReport) {
                if (in_array($fn, $suppressDailyMaintenanceFields) || in_array($flClean, $suppressDailyMaintenanceFields)) {
                    return false;
                }
            }

            return true;
        });

        // Collect all individual media items (including multi-photo JSON array)
        $mediaItems = [];
        foreach ($submission->values as $val) {
            $fieldLabel = $val->formField?->field_label ?? ucwords(str_replace('_', ' ', (string)$val->field_name));
            $isMedia = in_array($val->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])
                || !empty($val->media_url)
                || !empty($val->file_path);

            if (!$isMedia) continue;

            $rawPaths = [];
            if (is_array($val->value_json) && !empty($val->value_json)) {
                $rawPaths = $val->value_json;
            } elseif (!empty($val->media_url)) {
                $rawPaths = [$val->media_url];
            } elseif (!empty($val->file_path)) {
                $rawPaths = [$val->file_path];
            } elseif (!empty($val->value_text) && (str_contains($val->value_text, 'reports/') || str_contains($val->value_text, 'storage/'))) {
                $rawPaths = array_map('trim', explode(',', $val->value_text));
            }

            $foundUrls = [];
            foreach ($rawPaths as $p) {
                if (empty($p) || !is_string($p)) continue;
                $clean = trim($p);
                
                // Abaikan jika berupa path lokal perangkat android
                if (str_starts_with($clean, '/data/user/') || str_starts_with($clean, 'data/user/') || str_contains($clean, 'cache/wm_')) {
                    continue;
                }

                // If it is a full URL
                if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
                    $clean = str_replace('/storage/storage/', '/storage/', $clean);
                    $url = $clean;
                } else {
                    if (str_starts_with($clean, 'storage/')) {
                        $clean = substr($clean, 8);
                    } elseif (str_starts_with($clean, '/storage/')) {
                        $clean = substr($clean, 9);
                    }
                    $url = asset('storage/' . ltrim($clean, '/'));
                }

                $foundUrls[] = [
                    'label' => $fieldLabel,
                    'url' => $url,
                    'path' => $clean,
                    'field_type' => $val->field_type,
                ];
            }

            // Fallback: Jika path di database rusak/lokal tapi file ada di disk server
            if (empty($foundUrls)) {
                $subId = $submission->id;
                $fieldId = $val->report_form_field_id;
                $pattern = "reports/*/report_{$subId}_{$fieldId}_*.jpg";
                $matches = glob(storage_path("app/public/{$pattern}"));
                if (empty($matches)) {
                    $pattern2 = "reports/*/report_{$subId}_*.jpg";
                    $matches = glob(storage_path("app/public/{$pattern2}"));
                }
                if (!empty($matches)) {
                    foreach ($matches as $match) {
                        $rel = str_replace(storage_path('app/public/'), '', $match);
                        $rel = str_replace('\\', '/', $rel);
                        $foundUrls[] = [
                            'label' => $fieldLabel,
                            'url' => asset('storage/' . ltrim($rel, '/')),
                            'path' => $rel,
                            'field_type' => $val->field_type,
                        ];
                    }
                }
            }

            // Jika ada lebih dari 1 foto untuk field ini, beri keterangan indeks
            $totalFieldPhotos = count($foundUrls);
            foreach ($foundUrls as $fIdx => &$fItem) {
                if ($totalFieldPhotos > 1) {
                    $fItem['display_label'] = $fItem['label'] . ' (' . ($fIdx + 1) . '/' . $totalFieldPhotos . ')';
                } else {
                    $fItem['display_label'] = $fItem['label'];
                }
            }
            unset($fItem);

            $mediaItems = array_merge($mediaItems, $foundUrls);
        }
        $mediaValues = collect($mediaItems);
    @endphp

    <div class="report-view-wrapper">
        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="alert-banner alert-success">
                <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-banner alert-danger">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 1.2rem;"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- BANNER HEADER CARD --}}
        <div class="report-banner-card">
            <div class="banner-left">
                <div class="banner-icon-box">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <div class="banner-title">
                        {{ $template->title }}
                    </div>
                    <div class="banner-subtitle">
                        <span>No. Laporan:</span>
                        <span class="code-badge">{{ $submission->submission_code }}</span>
                        <span>&bull;</span>
                        <span>Disubmit pada: <strong>{{ $submission->submitted_at ? $submission->submitted_at->translatedFormat('d F Y, H:i:s') . ' WIB' : '-' }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="banner-actions">
                {{-- STATUS BADGE --}}
                <div class="status-badge" style="background-color: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['color'] }}; border-color: {{ $statusConfig['border'] }};">
                    <i class="fa-solid {{ $statusConfig['icon'] }}"></i>
                    <span>{{ $statusConfig['label'] }}</span>
                </div>

                {{-- ACTION: APPROVE --}}
                @if(in_array($status, ['pending', 'submitted', 'rejected']))
                    <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $submission->id, 'p' => $tenantPrincipal->id]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui (verifikasi valid) laporan ini?');">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn-action-approve">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Setujui Laporan</span>
                        </button>
                    </form>
                @endif

                {{-- ACTION: REJECT --}}
                @if(in_array($status, ['pending', 'submitted', 'approved', 'verified']))
                    <button type="button" class="btn-action-reject" onclick="openRejectModal()">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Tolak Laporan</span>
                    </button>
                @endif

                {{-- BACK BUTTON --}}
                <a href="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id, 'tab' => 'live']) }}" class="btn-portal-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        {{-- 2-COLUMN OVERVIEW GRID --}}
        <div class="overview-grid">
            {{-- CARD 1: INFORMASI PROMOTOR & OUTLET --}}
            <div class="overview-card">
                <div class="overview-card-title">
                    <i class="fa-solid fa-user-tie" style="color: #0F52BA;"></i>
                    <span>Informasi Pelapor & Toko</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Nama Promotor / SPG</span>
                    <span class="info-value">
                        {{ $employee?->full_name ?? $employee?->name ?? '-' }}
                        @if($employee?->nik)
                            <span style="font-weight: 500; color: var(--text-muted); font-size: 0.8rem;">({{ $employee->nik }})</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Prinsiple / Brand</span>
                    <span class="info-value" style="color: #0F52BA;">
                        {{ $tenantPrincipal->name }}
                    </span>
                </div>

                @php
                    $duluxAreaToRsmMap = [
                        'ACEH' => 'North Sumatera', 'MEDAN' => 'North Sumatera', 'BATAM' => 'Central Sumatera',
                        'PADANG' => 'Central Sumatera', 'PEKANBARU' => 'Central Sumatera', 'LAMPUNG' => 'South Sumatera',
                        'PALEMBANG' => 'South Sumatera', 'JAMBI' => 'South Sumatera', 'BENGKULU' => 'South Sumatera',
                        'BALIKPAPAN' => 'Kalimantan', 'SAMARINDA' => 'Kalimantan', 'BONTANG' => 'Kalimantan',
                        'BANJARMASIN' => 'Kalimantan', 'PONTIANAK' => 'Kalimantan', 'KENDARI' => 'Sulawesi',
                        'MAKASSAR' => 'Sulawesi', 'MANADO' => 'Sulawesi', 'PALU' => 'Sulawesi',
                        'MALUKU' => 'Sulawesi', 'PAPUA' => 'Sulawesi', 'CIBUBUR' => 'Greater Jakarta',
                        'GARUT' => 'West Java', 'BANDUNG' => 'West Java', 'CIREBON' => 'West Java',
                        'TASIKMALAYA' => 'West Java', 'BOGOR' => 'Greater Jakarta', 'BEKASI' => 'Greater Jakarta',
                        'DEPOK' => 'Greater Jakarta', 'TANGERANG' => 'Greater Jakarta', 'JAKARTA BARAT' => 'Greater Jakarta',
                        'JAKARTA PUSAT' => 'Greater Jakarta', 'JAKARTA UTARA' => 'Greater Jakarta', 'JAKARTA TIMUR' => 'Greater Jakarta',
                        'JAKARTA SELATAN' => 'Greater Jakarta', 'JAKARTA' => 'Greater Jakarta', 'MADIUN' => 'East Java',
                        'SURABAYA' => 'East Java', 'MALANG' => 'East Java', 'KEDIRI' => 'East Java',
                        'BANYUWANGI' => 'East Java', 'JEMBER' => 'Bali Nusra', 'BALI' => 'Bali Nusra',
                        'LOMBOK' => 'Bali Nusra', 'KUPANG' => 'Bali Nusra', 'SEMARANG' => 'North Central Java',
                        'TEGAL' => 'North Central Java', 'PEKALONGAN' => 'North Central Java', 'KUDUS' => 'North Central Java',
                        'SOLO' => 'South Central Java', 'YOGYAKARTA' => 'South Central Java', 'PURWOKERTO' => 'South Central Java',
                        'MAGELANG' => 'South Central Java', 'CENTRAL JAVA' => 'Central Java',
                    ];
                    $storeArea = $submission->workLocation?->branch?->name ?? ($submission->workLocation?->area?->name ?? ($submission->workLocation?->area ?? ($employee?->branch?->name ?? '-')));
                    $cleanArea = strtoupper(trim((string)$storeArea));
                    $rsmDulux = $duluxAreaToRsmMap[$cleanArea] ?? null;
                    if (!$rsmDulux) {
                        foreach ($duluxAreaToRsmMap as $city => $r) {
                            if ($city !== '' && str_contains($cleanArea, $city)) { $rsmDulux = $r; break; }
                        }
                    }
                    if (!$rsmDulux && !empty($submission->workLocation?->region) && $submission->workLocation?->region !== '-') {
                        $rsmDulux = $submission->workLocation?->region;
                    }
                    $rsmDulux = $rsmDulux ?: '-';
                @endphp
                <div class="info-row">
                    <span class="info-label">Area / Kota</span>
                    <span class="info-value">
                        {{ $storeArea }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">RSM Dulux</span>
                    <span class="info-value">
                        <span style="background: rgba(15, 82, 186, 0.1); color: #0F52BA; font-weight: 700; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem;">{{ $rsmDulux }}</span>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Toko / Outlet Tujuan</span>
                    <span class="info-value" style="font-weight: 800;">
                        {{ $storeName }}
                    </span>
                </div>

                @if($submission->verified_at)
                    <div class="info-row" style="border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                        <span class="info-label">Diverifikasi Oleh</span>
                        <span class="info-value">
                            {{ $submission->verifier?->name ?? 'Admin Prinsiple' }}
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; font-weight: 500;">
                                {{ $submission->verified_at->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- CARD 2: VALIDASI GPS & LOKASI --}}
            <div class="overview-card">
                <div class="overview-card-title">
                    <i class="fa-solid fa-location-dot" style="color: #0F52BA;"></i>
                    <span>Lokasi & Validasi Presensi GPS</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Status Radius Toko</span>
                    <span class="info-value">
                        @if($submission->is_within_radius)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #dcfce7; color: #15803d;">
                                <i class="fa-solid fa-circle-check"></i> Dalam Radius Toko
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: #fee2e2; color: #b91c1c;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Di Luar Radius Toko
                            </span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Koordinat GPS</span>
                    <span class="info-value">
                        @if($coordinates)
                            <span style="font-family: monospace; font-size: 0.82rem;">{{ $coordinates }}</span>
                            @if($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" class="media-full-btn" style="margin-left: 6px; font-size: 0.8rem; text-decoration: underline;">
                                    <span>Buka Maps ↗</span>
                                </a>
                            @endif
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Alamat Geocoding</span>
                    <span class="info-value" style="font-size: 0.82rem; line-height: 1.35; font-weight: 500;">
                        {{ $submission->address ?? ($submission->workLocation?->address ?? 'Alamat geocoding tidak tercatat') }}
                    </span>
                </div>

                @if($submission->verification_notes)
                    <div class="info-row" style="border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                        <span class="info-label">Catatan Verifikasi</span>
                        <span class="info-value" style="color: #b45309; font-style: italic;">
                            "{{ $submission->verification_notes }}"
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- PANEL RINGKASAN GLOBAL TRANSAKSI OFFTAKE (AKUMULATIF GLOBAL) --}}
        @if($hasDynamicOfftakeItems)
            <div class="offtake-summary-grid">
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Grand Total Penjualan</span>
                        <span class="offtake-stat-value" style="color: #15803d;">Rp {{ number_format($offtakeGlobalData['total_nilai_sales_rp'], 0, ',', '.') }}</span>
                        <span class="offtake-stat-sub">Akumulasi {{ count($offtakeItemsList) }} produk terjual</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(15, 82, 186, 0.12); color: #0F52BA;">
                        <i class="fa-solid fa-fill-drip"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Grand Total Volume</span>
                        <span class="offtake-stat-value" style="color: #0F52BA;">{{ number_format($offtakeGlobalData['total_volume_liter'], 2, ',', '.') }} L</span>
                        <span class="offtake-stat-sub">Total volume cat terjual</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Kuantiti Terjual</span>
                        <span class="offtake-stat-value" style="color: #4f46e5;">{{ number_format($offtakeGlobalData['total_volume_unit']) }} Unit</span>
                        <span class="offtake-stat-sub">Akumulasi Tin + Galon + Pail</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                        <i class="fa-solid fa-users-line"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Traffic & Pangsa Pasar</span>
                        <span class="offtake-stat-value" style="color: #b45309;">{{ $offtakeGlobalData['estimasi_market_share_persen'] ?? '0%' }}</span>
                        <span class="offtake-stat-sub" title="Cust Masuk: {{ $offtakeGlobalData['jml_customer_masuk'] ?? 0 }} | Beli Cat: {{ $offtakeGlobalData['jml_customer_beli_cat'] ?? 0 }} | Beli Dulux: {{ $offtakeGlobalData['jml_customer_beli_dulux'] ?? 0 }}">
                            Masuk: {{ $offtakeGlobalData['jml_customer_masuk'] ?? 0 }} | Beli Cat: {{ $offtakeGlobalData['jml_customer_beli_cat'] ?? 0 }} | Beli Dulux: {{ $offtakeGlobalData['jml_customer_beli_dulux'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- PANEL RINGKASAN GLOBAL PENJUALAN EVENT MBR WINGS SURYA (CARD STATISTIK) --}}
        @if($hasDynamicMbrSalesItems)
            <div class="offtake-summary-grid">
                {{-- CARD 1: TOTAL NILAI PENJUALAN --}}
                <div class="offtake-stat-card" style="border-left: 4px solid #16a34a;">
                    <div class="offtake-stat-icon" style="background: rgba(22, 163, 74, 0.12); color: #16a34a;">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Nilai Penjualan</span>
                        <span class="offtake-stat-value" style="color: #15803d;">
                            Rp {{ number_format($mbrGlobalData['total_value_penjualan_rp'], 0, ',', '.') }}
                        </span>
                        <span class="offtake-stat-sub">Akumulasi seluruh transaksi event MBR</span>
                    </div>
                </div>

                {{-- CARD 2: TOTAL KUANTITI TERJUAL --}}
                <div class="offtake-stat-card" style="border-left: 4px solid #d97706;">
                    <div class="offtake-stat-icon" style="background: rgba(217, 119, 6, 0.12); color: #d97706;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Kuantiti Terjual</span>
                        <span class="offtake-stat-value" style="color: #b45309;">
                            {{ number_format($mbrGlobalData['total_qty_penjualan']) }} <span style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Pcs</span>
                        </span>
                        <span class="offtake-stat-sub">Dari {{ count($mbrSalesItemsList) }} macam produk terjual</span>
                    </div>
                </div>

                {{-- CARD 3: TOTAL BAYAR DI BOOTH --}}
                <div class="offtake-stat-card" style="border-left: 4px solid #0284c7;">
                    <div class="offtake-stat-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Bayar di Booth (SPG)</span>
                        <span class="offtake-stat-value" style="color: #0369a1;">
                            Rp {{ number_format($mbrGlobalData['total_bayar_di_booth_rp'], 0, ',', '.') }}
                        </span>
                        <span class="offtake-stat-sub">Pembayaran langsung di booth SPG</span>
                    </div>
                </div>

                {{-- CARD 4: TOTAL BAYAR DI KASIR --}}
                <div class="offtake-stat-card" style="border-left: 4px solid #7c3aed;">
                    <div class="offtake-stat-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Bayar di Kasir Toko</span>
                        <span class="offtake-stat-value" style="color: #6d28d9;">
                            Rp {{ number_format($mbrGlobalData['total_bayar_di_kasir_rp'], 0, ',', '.') }}
                        </span>
                        <span class="offtake-stat-sub">Struk transaksi bayar di kasir outlet</span>
                    </div>
                </div>
            </div>
        @endif
        {{-- BANNER KHUSUS JIKA TOKO BEBAS OOS (STOK LENGKAP) --}}
        @if(strtolower($oosGlobalData['tipe_laporan_oos'] ?? '') === 'no_oos')
            <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.85rem; flex-shrink: 0; border: 1px solid #bbf7d0;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <h3 style="margin: 0 0 0.25rem 0; font-size: 1.15rem; font-weight: 800; color: #166534;">
                        No OOS — Stok Dulux Lengkap & Prima
                    </h3>
                    <p style="margin: 0; font-size: 0.88rem; color: #15803d; line-height: 1.45;">
                        Promotor / SPG melaporkan bahwa outlet ini memiliki ketersediaan stok seluruh lini produk Dulux secara lengkap. Tidak ada produk yang mengalami Out of Stock (OOS) pada kunjungan ini.
                    </p>
                </div>
            </div>
        @endif

        {{-- PANEL RINGKASAN GLOBAL OUT OF STOCK (OOS) --}}
        @if($hasDynamicOosItems)
            <div class="offtake-summary-grid">
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total SKU Out of Stock</span>
                        <span class="offtake-stat-value" style="color: #dc2626;">{{ count($oosItemsList) }} SKU</span>
                        <span class="offtake-stat-sub">Produk kosong terdata di outlet</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(234, 88, 12, 0.12); color: #ea580c;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Durasi OOS Terlama</span>
                        <span class="offtake-stat-value" style="color: #ea580c;">{{ $oosGlobalData['max_lama_oos'] }} Hari</span>
                        <span class="offtake-stat-sub">Maksimal durasi kekosongan stok</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(15, 82, 186, 0.12); color: #0F52BA;">
                        <i class="fa-solid fa-cart-arrow-down"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Saran Reorder</span>
                        <span class="offtake-stat-value" style="color: #0F52BA;">{{ $oosGlobalData['total_saran_qty'] }} Unit</span>
                        <span class="offtake-stat-sub">Rekomendasi kuantiti order toko</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- PANEL RINGKASAN GLOBAL STOCK END DULUX --}}
        @if($hasDynamicStockItems)
            <div class="offtake-summary-grid">
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(15, 82, 186, 0.12); color: #0F52BA;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total SKU Dilaporkan</span>
                        <span class="offtake-stat-value" style="color: #0F52BA;">{{ count($stockItemsList) }} SKU</span>
                        <span class="offtake-stat-sub">Item produk stock end terdata</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                        <i class="fa-solid fa-fill-drip"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Volume Stok</span>
                        <span class="offtake-stat-value" style="color: #059669;">{{ number_format($stockGlobalData['total_volume_liter'], 2, ',', '.') }} L</span>
                        <span class="offtake-stat-sub">Akumulasi volume seluruh produk</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Kemasan Galon</span>
                        <span class="offtake-stat-value" style="color: #4f46e5;">{{ number_format($stockGlobalData['total_qty_galon']) }} Galon</span>
                        <span class="offtake-stat-sub">Ukuran 2.5 Liter</span>
                    </div>
                </div>
                <div class="offtake-stat-card">
                    <div class="offtake-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                        <i class="fa-solid fa-cube"></i>
                    </div>
                    <div class="offtake-stat-info">
                        <span class="offtake-stat-label">Total Kemasan Pail</span>
                        <span class="offtake-stat-value" style="color: #d97706;">{{ number_format($stockGlobalData['total_qty_pail']) }} Pail</span>
                        <span class="offtake-stat-sub">Ukuran 20 Liter</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- PANEL RINGKASAN GLOBAL DATABASE PELANGGAN & KONSUMEN DULUX --}}
        @if($isCustomerDbReport)
            <div class="cust-summary-grid">
                {{-- CARD 1: PROFIL KONSUMEN --}}
                <div class="cust-stat-card">
                    <div class="cust-stat-icon blue">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="cust-stat-info">
                        <span class="cust-stat-label">Profil Konsumen</span>
                        <span class="cust-stat-value">{{ $custNama }}</span>
                        <span class="cust-stat-sub">
                            <i class="fa-solid fa-id-badge" style="color: #0F52BA;"></i> {{ $custTipe }}
                        </span>
                    </div>
                </div>

                {{-- CARD 2: KEPUTUSAN PEMBELIAN & BRAND SWITCH --}}
                <div class="cust-stat-card">
                    <div class="cust-stat-icon {{ $isBrandSwitch ? 'gold' : ($isDuluxBought ? 'emerald' : 'rose') }}">
                        <i class="fa-solid {{ $isBrandSwitch ? 'fa-shuffle' : ($isDuluxBought ? 'fa-shield-halved' : 'fa-triangle-exclamation') }}"></i>
                    </div>
                    <div class="cust-stat-info">
                        <span class="cust-stat-label">Brand Dibeli</span>
                        <span class="cust-stat-value" style="font-size: 1.05rem; color: {{ $isBrandSwitch ? '#0284c7' : ($isDuluxBought ? '#15803d' : '#e11d48') }};" title="{{ $custBrandDibeli }}">
                            {{ Str::limit($custBrandDibeli, 28) }}
                        </span>
                        <span class="cust-stat-sub">
                            @if($isBrandSwitch)
                                <strong style="color: #0284c7;">🎯 Beralih ke Dulux</strong> (Cari: {{ $custBrandDicari }})
                            @elseif($isLoyalDulux)
                                <strong style="color: #15803d;">🛡️ Konsumen Loyal Dulux</strong>
                            @elseif($isCompetitorBought)
                                <strong style="color: #e11d48;">⚠️ Merk Kompetitor</strong>
                            @else
                                Awal dicari: {{ $custBrandDicari ?: '-' }}
                            @endif
                        </span>
                    </div>
                </div>

                {{-- CARD 3: ESTIMASI NILAI BELANJA --}}
                <div class="cust-stat-card">
                    <div class="cust-stat-icon emerald">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div class="cust-stat-info">
                        <span class="cust-stat-label">Estimasi Nilai Belanja</span>
                        <span class="cust-stat-value" style="color: #15803d;">
                            Rp {{ number_format($custNilaiBelanja, 0, ',', '.') }}
                        </span>
                        <span class="cust-stat-sub">
                            <i class="fa-solid fa-bullseye" style="color: #10b981;"></i> {{ $custTujuan }}
                        </span>
                    </div>
                </div>

                {{-- CARD 4: VISUALIZER & PROGRAM MITRA --}}
                <div class="cust-stat-card">
                    <div class="cust-stat-icon purple">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div class="cust-stat-info">
                        <span class="cust-stat-label">Visualizer & Mitra</span>
                        <span class="cust-stat-value" style="font-size: 1rem;">
                            Visualizer: <strong style="color: {{ stripos($custPreview, 'ya') !== false ? '#15803d' : '#64748b' }};">{{ stripos($custPreview, 'ya') !== false ? 'Ya (Demo)' : 'Tidak' }}</strong>
                        </span>
                        <span class="cust-stat-sub" title="{{ $custLoyalty }}">
                            Mitra Dulux: <strong style="color: {{ (stripos($custLoyalty, 'bersedia') !== false && stripos($custLoyalty, 'tidak') === false) ? '#15803d' : '#64748b' }};">{{ (stripos($custLoyalty, 'bersedia') !== false && stripos($custLoyalty, 'tidak') === false) ? 'Bersedia' : 'Tidak Bersedia' }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        @elseif($isDailyMaintenanceReport)
            <div class="dm-summary-grid">
                {{-- CARD 1: IDENTITAS MESIN --}}
                <div class="dm-stat-card">
                    <div class="dm-stat-icon blue">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                    <div class="dm-stat-info">
                        <span class="dm-stat-label">Mesin Tinting POS</span>
                        <span class="dm-stat-value" title="{{ $dmTipeMesin }}">{{ Str::limit($dmTipeMesin, 22) }}</span>
                        <span class="dm-stat-sub">
                            <i class="fa-solid fa-barcode" style="color: #0F52BA;"></i> S/N: {{ $dmNoMesin ?: '-' }}
                        </span>
                    </div>
                </div>

                {{-- CARD 2: KEBERSIHAN NOZZLE & BRUSH --}}
                <div class="dm-stat-card">
                    <div class="dm-stat-icon {{ $nozzleTone['type'] === 'danger' ? 'rose' : ($nozzleTone['type'] === 'warning' ? 'amber' : 'emerald') }}">
                        <i class="fa-solid {{ $nozzleTone['type'] === 'danger' ? 'fa-triangle-exclamation' : ($nozzleTone['type'] === 'warning' ? 'fa-broom' : 'fa-spray-can-sparkles') }}"></i>
                    </div>
                    <div class="dm-stat-info">
                        <span class="dm-stat-label">Kebersihan Nozzle</span>
                        <span class="dm-stat-value" style="color: {{ $nozzleTone['color'] }}; font-size: 1.05rem;" title="{{ $dmNozzle }}">
                            {{ Str::limit($dmNozzle, 25) }}
                        </span>
                        <span class="dm-stat-sub">
                            <i class="fa-solid {{ $nozzleTone['icon'] }}" style="color: {{ $nozzleTone['color'] }};"></i> Brush & Sponge Cleaning
                        </span>
                    </div>
                </div>

                {{-- CARD 3: SIRKULASI PASTA TINTER --}}
                <div class="dm-stat-card">
                    <div class="dm-stat-icon {{ $sirkulasiTone['type'] === 'danger' ? 'rose' : ($sirkulasiTone['type'] === 'warning' ? 'amber' : 'emerald') }}">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <div class="dm-stat-info">
                        <span class="dm-stat-label">Sirkulasi Tinter</span>
                        <span class="dm-stat-value" style="color: {{ $sirkulasiTone['color'] }}; font-size: 1.05rem;" title="{{ $dmSirkulasi }}">
                            {{ Str::limit($dmSirkulasi, 25) }}
                        </span>
                        <span class="dm-stat-sub">
                            <i class="fa-solid {{ $sirkulasiTone['icon'] }}" style="color: {{ $sirkulasiTone['color'] }};"></i> Agitasi & Aliran Pigmen
                        </span>
                    </div>
                </div>

                {{-- CARD 4: SOFTWARE MIX2WIN & KOMPUTER --}}
                <div class="dm-stat-card">
                    <div class="dm-stat-icon {{ ($softwareTone['type'] === 'danger' || $mix2winTone['type'] === 'danger') ? 'rose' : (($softwareTone['type'] === 'warning' || $mix2winTone['type'] === 'warning') ? 'amber' : 'purple') }}">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <div class="dm-stat-info">
                        <span class="dm-stat-label">Software & Mix2Win</span>
                        <span class="dm-stat-value" style="font-size: 1.02rem;" title="{{ $dmMix2win }}">
                            Mix2Win: <strong style="color: {{ $mix2winTone['color'] }};">{{ Str::limit($dmMix2win, 18) }}</strong>
                        </span>
                        <span class="dm-stat-sub" title="{{ $dmSoftware }}">
                            PC: <strong style="color: {{ $softwareTone['color'] }};">{{ Str::limit($dmSoftware, 20) }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- SECTION 2: SPLIT CONTENT (DATA FORM TABLE + PHOTO GALLERY) --}}
        <div class="content-split-grid @if($mediaValues->isEmpty()) no-media @endif">
            {{-- PANEL 1: RINCIAN PRODUK TERJUAL (ATAU PARAMETER FORMULIR STANDAR) --}}
            <div class="panel-container">
                @if($hasDynamicOfftakeItems)
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-basket-shopping" style="color: #0F52BA;"></i>
                            <span>Rincian Produk Terjual</span>
                        </div>
                        <span class="panel-count-badge" style="background: #dbeafe; color: #1d4ed8; font-weight: 800;">
                            {{ count($offtakeItemsList) }} Produk Terjual
                        </span>
                    </div>

                    <div style="padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
                        @foreach($offtakeItemsList as $pIdx => $pItem)
                            @php
                                $pBrand = $pItem['brand'] ?? 'Dulux';
                                $pName = $pItem['sub_brand'] ?? ($pItem['product_name'] ?? 'Produk Cat');
                                $pSub1 = $pItem['sub_brand1'] ?? $pName;
                                $pSub2 = $pItem['sub_brand2'] ?? '-';
                                $pRmBase = $pItem['brand_rm_base'] ?? '-';
                                $hTin = (float)($pItem['harga_tin'] ?? 0);
                                $hGalon = (float)($pItem['harga_galon'] ?? 0);
                                $hPail = (float)($pItem['harga_pail'] ?? 0);
                                $qTin = (int)($pItem['qty_tin'] ?? 0);
                                $qGalon = (int)($pItem['qty_galon'] ?? 0);
                                $qPail = (int)($pItem['qty_pail'] ?? 0);
                                $vTin = (float)($pItem['volume_tin_l'] ?? 0);
                                $vGalon = (float)($pItem['volume_galon_l'] ?? 0);
                                $vPail = (float)($pItem['volume_pail_l'] ?? 0);
                                $totUnit = (int)($pItem['total_unit'] ?? ($qTin + $qGalon + $qPail));
                                $totLiter = (float)($pItem['total_liter'] ?? ($vTin + $vGalon + $vPail));
                                $totRp = (float)($pItem['total_nilai_rp'] ?? 0);
                            @endphp
                            <div class="product-breakdown-card">
                                {{-- CARD HEADER --}}
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #0F52BA; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $pIdx + 1 }}</span>
                                        <span class="brand-tag {{ strtolower($pBrand) === 'catylac' ? 'brand-tag-catylac' : 'brand-tag-dulux' }}" style="font-size: 0.78rem; font-weight: 800; padding: 2px 8px; border-radius: 6px;">
                                            {{ $pBrand }}
                                        </span>
                                        <strong style="font-size: 0.95rem; color: var(--text-heading); font-weight: 800;">{{ $pName }}</strong>
                                        @if(!empty($pRmBase) && $pRmBase !== '-' && $pRmBase !== $pBrand)
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;">
                                                {{ $pRmBase }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <span style="font-size: 0.9rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 4px 10px; border-radius: 8px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-rupiah-sign" style="font-size: 0.78rem;"></i>
                                            Rp {{ number_format($totRp, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- CARD SPECS GRID --}}
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem;">
                                    {{-- 1. HARGA STANDART / ACUAN --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-tag" style="color: #0F52BA;"></i> Harga Standart Acuan
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Galon ({{ $pItem['kemasan_galon'] ?? '2.5L' }}):</span>
                                                <strong style="color: {{ $hGalon > 0 ? '#1e293b' : '#94a3b8' }};">Rp {{ number_format($hGalon, 0, ',', '.') }}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Pail ({{ $pItem['kemasan_pail'] ?? '20L' }}):</span>
                                                <strong style="color: {{ $hPail > 0 ? '#1e293b' : '#94a3b8' }};">Rp {{ number_format($hPail, 0, ',', '.') }}</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Tin ({{ $pItem['kemasan_tin'] ?? '1L' }}):</span>
                                                <strong style="color: {{ $hTin > 0 ? '#1e293b' : '#94a3b8' }};">Rp {{ number_format($hTin, 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. KUANTITI TERJUAL --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-cart-shopping" style="color: #10b981;"></i> Kuantiti Terjual
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Galon:</span>
                                                <strong style="color: {{ $qGalon > 0 ? '#15803d' : '#94a3b8' }};">{{ $qGalon }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Pail:</span>
                                                <strong style="color: {{ $qPail > 0 ? '#15803d' : '#94a3b8' }};">{{ $qPail }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Tin:</span>
                                                <strong style="color: {{ $qTin > 0 ? '#15803d' : '#94a3b8' }};">{{ $qTin }} Unit</strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 2px; margin-top: 1px;">
                                                <span style="font-weight: 700; color: #1e293b;">Total Qty:</span>
                                                <strong style="color: #0F52BA; font-weight: 800;">{{ $totUnit }} Unit</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. TOTAL DALAM LITER (VOLUME) --}}
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-fill-drip" style="color: #0284c7;"></i> Total Volume (Liter)
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.78rem;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Vol Galon:</span>
                                                <span style="font-weight: 600;">{{ number_format($vGalon, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Vol Pail:</span>
                                                <span style="font-weight: 600;">{{ number_format($vPail, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span style="color: #64748b;">Vol Tin:</span>
                                                <span style="font-weight: 600;">{{ number_format($vTin, 2) }} L</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 2px; margin-top: 1px;">
                                                <span style="font-weight: 700; color: #1e293b;">Total Volume:</span>
                                                <strong style="color: #0284c7; font-weight: 800;">{{ number_format($totLiter, 2) }} Liter</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @elseif($hasDynamicMbrSalesItems)
                    <div class="panel-header" style="border-bottom: 2px solid #fee2e2;">
                        <div class="panel-title">
                            <i class="fa-solid fa-cart-shopping" style="color: #dc2626;"></i>
                            <span>Rincian Produk Penjualan Event MBR</span>
                        </div>
                        <span class="panel-count-badge" style="background: #fee2e2; color: #b91c1c; font-weight: 800;">
                            {{ count($mbrSalesItemsList) }} Produk Terjual
                        </span>
                    </div>

                    <div style="padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
                        @foreach($mbrSalesItemsList as $pIdx => $pItem)
                            @php
                                $pName = $pItem['name'] ?? ($pItem['product_name'] ?? ($pItem['deskripsi'] ?? 'Produk Wings'));
                                $pSku = $pItem['sku_code'] ?? ($pItem['sku'] ?? '-');
                                $pDist = (float)($pItem['distributor_price'] ?? ($pItem['harga_distributor'] ?? ($pItem['harga_jual_distributor'] ?? 0)));
                                $pStore = (float)($pItem['store_price'] ?? ($pItem['harga_toko'] ?? 0));
                                $pQty = (int)($pItem['qty'] ?? 0);
                                $pValue = (float)($pItem['value_rp'] ?? ($pStore * $pQty));
                                $pPayType = $pItem['payment_type'] ?? ($pItem['jenis_pembayaran'] ?? '-');
                                $pStrukPhoto = $pItem['struk_photo_url'] ?? ($pItem['struk_photo_path'] ?? ($pItem['foto_struk'] ?? ($pItem['photo_struk_url'] ?? null)));
                            @endphp
                            <div class="product-breakdown-card" style="border-left: 4px solid #dc2626; background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; border-left-width: 4px; padding: 0.85rem 1rem;">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #dc2626; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $pIdx + 1 }}</span>
                                        <strong style="font-size: 0.95rem; color: var(--text-heading); font-weight: 800;">{{ $pName }}</strong>
                                        @if(!empty($pSku) && $pSku !== '-')
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;">
                                                {{ $pSku }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <span style="font-size: 0.85rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 3px 10px; border-radius: 6px; border: 1px solid #bbf7d0;">
                                            Subtotal: Rp {{ number_format($pValue, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.6rem; font-size: 0.82rem; background: #f8fafc; padding: 0.65rem; border-radius: 8px; border: 1px solid #f1f5f9;">
                                    <div>
                                        <span style="color: #64748b; font-size: 0.75rem; display: block;">Harga Distributor</span>
                                        <strong style="color: #0284c7;">{{ $pDist > 0 ? 'Rp ' . number_format($pDist, 0, ',', '.') : '-' }}</strong>
                                    </div>
                                    <div>
                                        <span style="color: #64748b; font-size: 0.75rem; display: block;">Harga Toko</span>
                                        <strong style="color: #1e293b;">Rp {{ number_format($pStore, 0, ',', '.') }}</strong>
                                    </div>
                                    <div>
                                        <span style="color: #64748b; font-size: 0.75rem; display: block;">Kuantiti Terjual</span>
                                        <strong style="color: #d97706;">{{ number_format($pQty) }} Pcs</strong>
                                    </div>
                                    <div>
                                        <span style="color: #64748b; font-size: 0.75rem; display: block;">Jenis Pembayaran</span>
                                        <span class="badge {{ str_contains(strtolower($pPayType), 'booth') ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-dark' }} border" style="font-size: 0.75rem;">
                                            {{ $pPayType }}
                                        </span>
                                    </div>
                                </div>

                                @if(!empty($pStrukPhoto))
                                    @php
                                        $strukPhotoUrl = Str::startsWith($pStrukPhoto, ['http://', 'https://']) ? $pStrukPhoto : Storage::url($pStrukPhoto);
                                        $strukModalTitle = 'Bukti Struk Transaksi: ' . ($p['product_name'] ?? 'Produk MBR');
                                    @endphp
                                    <div style="margin-top: 0.65rem; display: flex; align-items: center; justify-content: space-between; background: #fff5f5; padding: 6px 12px; border-radius: 8px; border: 1px solid #fed7d7;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="{{ $strukPhotoUrl }}" alt="Struk" onclick="openPhotoModal('{{ $strukPhotoUrl }}', '{{ addslashes($strukModalTitle) }}')" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 1px solid #fca5a5; cursor: pointer;" title="Klik untuk memperbesar">
                                            <div>
                                                <span style="font-size: 0.75rem; font-weight: 700; color: #dc2626; display: block;"><i class="fa-solid fa-receipt me-1"></i> Foto Struk Produk:</span>
                                                <button type="button" onclick="openPhotoModal('{{ $strukPhotoUrl }}', '{{ addslashes($strukModalTitle) }}')" style="background: none; border: none; padding: 0; font-size: 0.76rem; color: #b91c1c; font-weight: 800; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                                    <i class="fa-solid fa-expand"></i> Lihat Bukti Struk Transaksi
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" onclick="openPhotoModal('{{ $strukPhotoUrl }}', '{{ addslashes($strukModalTitle) }}')" style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 4px 10px; font-size: 0.72rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-magnifying-glass-plus"></i> Preview
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @elseif($hasDynamicOosItems)
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-boxes-packing" style="color: #dc2626;"></i>
                            <span>Rincian Produk Out of Stock (OOS)</span>
                        </div>
                        <span class="panel-count-badge" style="background: #fee2e2; color: #b91c1c; font-weight: 800;">
                            {{ count($oosItemsList) }} Produk OOS
                        </span>
                    </div>

                    <div style="padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
                        @foreach($oosItemsList as $pIdx => $pItem)
                            @php
                                $pName = $pItem['product_name'] ?? ($pItem['produk_oos'] ?? 'Produk Dulux');
                                $kemasan = $pItem['kemasan_size_oos'] ?? '-';
                                $base = $pItem['base_warna_oos'] ?? '-';
                                $readyMix = $pItem['warna_ready_mix_oos'] ?? '-';
                                $lama = max(1, (int)($pItem['lama_oos_hari'] ?? ($pItem['calculated_lama_oos'] ?? 1)));
                                $saran = (int)($pItem['saran_qty_order'] ?? 0);
                                $alasan = $pItem['alasan_oos'] ?? 'PO belum kirim / kendala stok';
                            @endphp
                            <div class="product-breakdown-card" style="border-left: 4px solid {{ $lama > 3 ? '#dc2626' : ($lama > 1 ? '#ea580c' : '#2563eb') }};">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: #dc2626; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $pIdx + 1 }}</span>
                                        <strong style="font-size: 0.95rem; color: var(--text-heading); font-weight: 800;">{{ $pName }}</strong>
                                        <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;">
                                            {{ $kemasan }}
                                        </span>
                                    </div>
                                    <div>
                                        <span style="font-size: 0.82rem; font-weight: 800; color: {{ $lama > 3 ? '#dc2626' : ($lama > 1 ? '#ea580c' : '#2563eb') }}; background: {{ $lama > 3 ? '#fee2e2' : ($lama > 1 ? '#ffedd5' : '#eff6ff') }}; padding: 3px 9px; border-radius: 6px; border: 1px solid {{ $lama > 3 ? '#fecaca' : ($lama > 1 ? '#fed7aa' : '#bfdbfe') }}; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-clock"></i>
                                            Lama OOS: {{ $lama }} Hari{{ $lama === 1 ? ' (Baru)' : '' }}
                                        </span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            Base / Varian Warna
                                        </div>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 0.85rem;">
                                            {{ $base }}
                                        </div>
                                        @if(!empty($readyMix) && $readyMix !== '-' && !str_contains($readyMix, 'Bukan'))
                                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">
                                                Warna Ready Mix: <strong style="color: #0b3d88;">{{ $readyMix }}</strong>
                                            </div>
                                        @endif
                                    </div>

                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            Saran Kuantiti Order
                                        </div>
                                        <div style="font-weight: 700; color: #0b3d88; font-size: 0.85rem;">
                                            {{ $saran > 0 ? ($saran . ' Unit / Kaleng') : '-' }}
                                        </div>
                                    </div>

                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; grid-column: 1 / -1;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            Penyebab / Alasan Out of Stock (OOS)
                                        </div>
                                        <div style="font-weight: 600; color: #b91c1c; font-size: 0.82rem;">
                                            {{ $alasan }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @elseif($hasDynamicStockItems)
                    <div class="panel-header" style="background: linear-gradient(135deg, rgba(15,82,186,0.06) 0%, rgba(16,185,129,0.04) 100%);">
                        <div class="panel-title">
                            <i class="fa-solid fa-boxes-stacked" style="color: #0F52BA;"></i>
                            <span>Rincian Produk Stock End (Multi-Produk)</span>
                        </div>
                        <span class="panel-count-badge" style="background: #dbeafe; color: #1e40af; font-weight: 800;">
                            {{ count($stockItemsList) }} Produk Terdata
                        </span>
                    </div>

                    <div style="padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
                        @foreach($stockItemsList as $sIdx => $sItem)
                            @php
                                $sName = $sItem['product_name'] ?? ($sItem['produk_stock_end'] ?? ($sItem['produk'] ?? 'Produk Dulux / Catylac'));
                                $sBrand = strtoupper(trim((string)($sItem['brand'] ?? (str_contains(strtolower($sName), 'catylac') ? 'CATYLAC' : 'DULUX'))));
                                $sKategori = $sItem['kategori_produk'] ?? ($sItem['kategori_cat'] ?? ($sItem['kategori'] ?? '-'));
                                $qGalon = (float)($sItem['stok_qty_galon'] ?? ($sItem['qty_galon'] ?? ($sItem['kuantiti_galon'] ?? 0)));
                                $qPail = (float)($sItem['stok_qty_pail'] ?? ($sItem['qty_pail'] ?? ($sItem['kuantiti_pail'] ?? 0)));
                                $vLiter = (float)($sItem['total_volume_liter'] ?? (($qGalon * 2.5) + ($qPail * 20.0)));
                                $baseWarna = $sItem['base_warna'] ?? ($sItem['base_cat'] ?? '-');
                            @endphp
                            <div class="product-breakdown-card" style="border-left: 4px solid {{ $sBrand === 'CATYLAC' ? '#f59e0b' : '#0F52BA' }};">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.65rem;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 0.75rem; font-weight: 800; background: {{ $sBrand === 'CATYLAC' ? '#d97706' : '#0F52BA' }}; color: #fff; padding: 2px 7px; border-radius: 6px;">#{{ $sIdx + 1 }}</span>
                                        <strong style="font-size: 0.95rem; color: var(--text-heading); font-weight: 800;">{{ $sName }}</strong>
                                        <span style="font-size: 0.72rem; font-weight: 800; color: {{ $sBrand === 'CATYLAC' ? '#b45309' : '#1d4ed8' }}; background: {{ $sBrand === 'CATYLAC' ? '#fef3c7' : '#dbeafe' }}; padding: 2px 8px; border-radius: 6px; border: 1px solid {{ $sBrand === 'CATYLAC' ? '#fde68a' : '#bfdbfe' }};">
                                            {{ $sBrand }}
                                        </span>
                                        @if(!empty($sKategori) && $sKategori !== '-')
                                            <span style="font-size: 0.74rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 7px; border-radius: 5px; border: 1px solid #e2e8f0;">
                                                {{ $sKategori }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <span style="font-size: 0.85rem; font-weight: 800; color: #047857; background: #d1fae5; padding: 3px 10px; border-radius: 6px; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-fill-drip"></i>
                                            {{ number_format($vLiter, 2, ',', '.') }} Liter
                                        </span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            <i class="fa-solid fa-box-archive" style="color: #6366f1;"></i> Kemasan Galon (2.5L)
                                        </div>
                                        <div style="font-weight: 800; color: {{ $qGalon > 0 ? '#1e293b' : '#94a3b8' }}; font-size: 0.95rem;">
                                            {{ number_format($qGalon) }} <span style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Galon ({{ number_format($qGalon * 2.5, 1) }} L)</span>
                                        </div>
                                    </div>

                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            <i class="fa-solid fa-cube" style="color: #f59e0b;"></i> Kemasan Pail (20L)
                                        </div>
                                        <div style="font-weight: 800; color: {{ $qPail > 0 ? '#1e293b' : '#94a3b8' }}; font-size: 0.95rem;">
                                            {{ number_format($qPail) }} <span style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Pail ({{ number_format($qPail * 20.0, 1) }} L)</span>
                                        </div>
                                    </div>

                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px;">
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                            <i class="fa-solid fa-palette" style="color: #ec4899;"></i> Base / Varian Warna
                                        </div>
                                        <div style="font-weight: 700; color: #1e293b; font-size: 0.88rem;">
                                            <span style="display: inline-block; background: #ede9fe; color: #6d28d9; padding: 2px 8px; border-radius: 5px; font-weight: 700; border: 1px solid #ddd6fe;">
                                                {{ $baseWarna }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Informasi Mesin Tinting & Tinter Tambahan</span>
                        </div>
                    @endif
                @elseif($isCustomerDbReport)
                    <div class="panel-header" style="background: linear-gradient(135deg, rgba(15,82,186,0.06) 0%, rgba(2,132,199,0.04) 100%);">
                        <div class="panel-title">
                            <i class="fa-solid fa-address-card" style="color: #0F52BA;"></i>
                            <span>Profil Konsumen & Analisis Perilaku Belanja Dulux</span>
                        </div>
                        <span class="panel-count-badge" style="background: #e0f2fe; color: #0369a1; font-weight: 800;">
                            Database Konsumen
                        </span>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1.15rem;">
                        {{-- 1. IDENTITAS & KONTAK PELANGGAN --}}
                        <div class="cust-detail-card">
                            <div class="cust-card-section-title">
                                <i class="fa-solid fa-user" style="color: #0F52BA;"></i>
                                <span>Identitas & Segmentasi Konsumen</span>
                            </div>
                            <div class="cust-persona-box">
                                <div class="cust-avatar-circle">
                                    {{ strtoupper(substr($custNama ?: 'P', 0, 2)) }}
                                </div>
                                <div class="cust-persona-info">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <h4 class="cust-persona-name">{{ $custNama }}</h4>
                                        <span class="cust-badge segment">{{ $custTipe }}</span>
                                        @if(stripos($custLoyalty, 'bersedia') !== false && stripos($custLoyalty, 'tidak') === false)
                                            <span class="cust-badge loyalty-yes"><i class="fa-solid fa-award"></i> Bersedia Mitra Dulux</span>
                                        @else
                                            <span class="cust-badge loyalty-no">Bukan Mitra</span>
                                        @endif
                                    </div>
                                    <div class="cust-contact-row">
                                        <div class="cust-contact-item">
                                            <i class="fa-solid fa-phone" style="color: #64748b;"></i>
                                            <span>{{ $custPhone }}</span>
                                            @if($waLink)
                                                <a href="{{ $waLink }}" target="_blank" class="btn-chat-wa" title="Kirim Pesan WhatsApp">
                                                    <i class="fa-brands fa-whatsapp"></i> Chat WA
                                                </a>
                                            @endif
                                        </div>
                                        <div class="cust-contact-item">
                                            <i class="fa-solid fa-location-dot" style="color: #64748b;"></i>
                                            <span>{{ $custAlamat }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. BRAND PREFERENCE & BUYING JOURNEY --}}
                        <div class="cust-detail-card">
                            <div class="cust-card-section-title">
                                <i class="fa-solid fa-shuffle" style="color: #0F52BA;"></i>
                                <span>Preferensi Brand & Konversi Pembelian (Buying Journey)</span>
                            </div>

                            {{-- CONVERSION ALERT BANNER --}}
                            @if($isBrandSwitch)
                                <div class="conversion-alert success">
                                    <div class="conv-icon"><i class="fa-solid fa-bullseye" style="color: #16a34a;"></i></div>
                                    <div>
                                        <strong style="font-size: 0.92rem; color: #166534; display: block; margin-bottom: 2px;">
                                            🎯 Brand Switching Berhasil! (Konsumen Beralih ke Dulux)
                                        </strong>
                                        <span style="font-size: 0.82rem; color: #15803d;">
                                            Konsumen awalnya mencari produk <strong>"{{ $custBrandDicari }}"</strong>, namun berkat konsultasi SPG/DC, konsumen memutuskan membeli produk <strong>"{{ $custBrandDibeli }}"</strong>.
                                        </span>
                                    </div>
                                </div>
                            @elseif($isLoyalDulux)
                                <div class="conversion-alert info">
                                    <div class="conv-icon"><i class="fa-solid fa-shield-halved" style="color: #2563eb;"></i></div>
                                    <div>
                                        <strong style="font-size: 0.92rem; color: #1e40af; display: block; margin-bottom: 2px;">
                                            🛡️ Brand Retention Terjaga (Konsumen Loyal Dulux)
                                        </strong>
                                        <span style="font-size: 0.82rem; color: #1d4ed8;">
                                            Konsumen datang mencari <strong>"{{ $custBrandDicari }}"</strong> dan konsisten melakukan transaksi pembelian produk <strong>"{{ $custBrandDibeli }}"</strong>.
                                        </span>
                                    </div>
                                </div>
                            @elseif($isCompetitorBought)
                                <div class="conversion-alert warning">
                                    <div class="conv-icon"><i class="fa-solid fa-triangle-exclamation" style="color: #ea580c;"></i></div>
                                    <div>
                                        <strong style="font-size: 0.92rem; color: #9a3412; display: block; margin-bottom: 2px;">
                                            ⚠️ Konsumen Memilih Brand Kompetitor
                                        </strong>
                                        <span style="font-size: 0.82rem; color: #c2410c;">
                                            Konsumen akhirnya membeli brand <strong>"{{ $custBrandDibeli }}"</strong> (Awal dicari: {{ $custBrandDicari }}).
                                        </span>
                                    </div>
                                </div>
                            @endif

                            {{-- VISUAL BRAND FLOW COMPARISON --}}
                            <div class="brand-flow-container">
                                <div class="brand-flow-box sought">
                                    <span class="flow-label"><i class="fa-solid fa-magnifying-glass"></i> Brand Awal Dicari / Ditanyakan</span>
                                    <div class="flow-brand-name">{{ $custBrandDicari ?: '-' }}</div>
                                    <span class="flow-sub">Kebutuhan awal saat datang ke toko</span>
                                </div>
                                <div class="flow-arrow">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                                <div class="brand-flow-box bought {{ $isDuluxBought ? 'dulux' : 'other' }}">
                                    <span class="flow-label"><i class="fa-solid fa-bag-shopping"></i> Brand Akhir yang Dibeli</span>
                                    <div class="flow-brand-name">{{ $custBrandDibeli ?: '-' }}</div>
                                    <span class="flow-sub">Keputusan akhir transaksi di kasir</span>
                                </div>
                            </div>

                            {{-- REASONS & INTERACTION ATTRIBUTES --}}
                            <div class="cust-attributes-grid">
                                <div class="attr-item">
                                    <span class="attr-label"><i class="fa-solid fa-comment-dots" style="color: #0F52BA;"></i> Alasan Memilih Brand</span>
                                    <strong class="attr-val highlight-blue">{{ $custAlasan }}</strong>
                                </div>
                                <div class="attr-item">
                                    <span class="attr-label"><i class="fa-solid fa-door-open" style="color: #0F52BA;"></i> Tujuan Datang ke Toko</span>
                                    <strong class="attr-val">{{ $custTujuan }}</strong>
                                </div>
                                <div class="attr-item">
                                    <span class="attr-label"><i class="fa-solid fa-paint-roller" style="color: #0F52BA;"></i> Tipe Pekerjaan Pengecatan</span>
                                    <strong class="attr-val">{{ $custTipePengecatan }}</strong>
                                </div>
                                <div class="attr-item">
                                    <span class="attr-label"><i class="fa-solid fa-eye" style="color: #0F52BA;"></i> Preview Warna Visualizer</span>
                                    <strong class="attr-val" style="color: {{ stripos($custPreview, 'ya') !== false ? '#15803d' : '#64748b' }};">
                                        {{ $custPreview }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        {{-- 3. ESTIMASI TOTAL NILAI PEMBELIAN (RUPIAH) --}}
                        <div class="cust-detail-card value-card">
                            <div class="value-card-left">
                                <div class="value-icon"><i class="fa-solid fa-receipt"></i></div>
                                <div>
                                    <span class="value-label">Estimasi Total Nilai Pembelian Konsumen</span>
                                    <div class="value-amount">Rp {{ number_format($custNilaiBelanja, 0, ',', '.') }}</div>
                                    <span class="value-note">Perkiraan nilai transaksi cat & material yang dibeli konsumen</span>
                                </div>
                            </div>
                            <div>
                                <span class="cust-badge value-badge"><i class="fa-solid fa-check-double"></i> Transaksi Tercatat</span>
                            </div>
                        </div>

                        {{-- 4. CATATAN KHUSUS KONSUMEN --}}
                        @if(!empty($custCatatan) && $custCatatan !== '-')
                            <div class="cust-detail-card note-card">
                                <div class="cust-card-section-title">
                                    <i class="fa-solid fa-note-sticky" style="color: #b45309;"></i>
                                    <span>Catatan Khusus / Preferensi Konsumen</span>
                                </div>
                                <div class="cust-note-box">
                                    <i class="fa-solid fa-quote-left quote-icon"></i>
                                    <p class="cust-note-text">{{ $custCatatan }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @elseif($isDailyMaintenanceReport)
                    <div class="panel-header" style="background: linear-gradient(135deg, rgba(15,82,186,0.06) 0%, rgba(2,132,199,0.04) 100%);">
                        <div class="panel-title">
                            <i class="fa-solid fa-screwdriver-wrench" style="color: #0F52BA;"></i>
                            <span>Hasil Inspeksi & Checklist Maintenance Mesin Tinting</span>
                        </div>
                        <span class="panel-count-badge" style="background: #e0f2fe; color: #0369a1; font-weight: 800;">
                            Daily Checklist
                        </span>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1.15rem;">
                        {{-- 1. IDENTITAS MESIN & STATUS KESEHATAN SISTEM --}}
                        <div class="dm-machine-banner">
                            <div class="dm-machine-left">
                                <div class="dm-machine-icon">
                                    <i class="fa-solid fa-gears"></i>
                                </div>
                                <div>
                                    <div class="dm-machine-title">{{ $dmTipeMesin }}</div>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span class="dm-machine-sn">
                                            <i class="fa-solid fa-barcode"></i> S/N: {{ $dmNoMesin ?: '-' }}
                                        </span>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                                            Petugas: <strong>{{ $employee?->full_name ?? $employee?->name ?? '-' }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="dm-health-badge" style="background: {{ $machineHealth['badge_bg'] }}; color: {{ $machineHealth['badge_color'] }}; border-color: {{ $machineHealth['badge_border'] }};">
                                    <i class="fa-solid {{ $machineHealth['icon'] }}"></i>
                                    <span>{{ $machineHealth['status'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- 2. 4-POINT TECHNICAL INSPECTION CHECKLIST GRID --}}
                        <div class="dm-checklist-grid">
                            {{-- POINT 1: NOZZLE CLEANING --}}
                            <div class="dm-item-card">
                                <div>
                                    <div class="dm-item-header">
                                        <div class="dm-item-title-group">
                                            <div class="dm-item-icon" style="background: {{ $nozzleTone['bg'] }}; color: {{ $nozzleTone['color'] }};">
                                                <i class="fa-solid fa-spray-can-sparkles"></i>
                                            </div>
                                            <div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Poin 1</span>
                                                <div class="dm-item-label">Nozzle & Sponge Cleaning</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dm-status-pill" style="background: {{ $nozzleTone['bg'] }}; color: {{ $nozzleTone['color'] }}; border-color: {{ $nozzleTone['border'] }};">
                                        <i class="fa-solid {{ $nozzleTone['icon'] }}"></i>
                                        <span>{{ $dmNozzle }}</span>
                                    </div>
                                    <div class="dm-item-desc">
                                        Pembersihan lubang nozzle dispenser dengan sikat halus & air hangat untuk mencegah pengeringan pigmen tinter.
                                    </div>
                                </div>
                            </div>

                            {{-- POINT 2: SIRKULASI TINTER --}}
                            <div class="dm-item-card">
                                <div>
                                    <div class="dm-item-header">
                                        <div class="dm-item-title-group">
                                            <div class="dm-item-icon" style="background: {{ $sirkulasiTone['bg'] }}; color: {{ $sirkulasiTone['color'] }};">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </div>
                                            <div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Poin 2</span>
                                                <div class="dm-item-label">Sirkulasi Pasta Tinter</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dm-status-pill" style="background: {{ $sirkulasiTone['bg'] }}; color: {{ $sirkulasiTone['color'] }}; border-color: {{ $sirkulasiTone['border'] }};">
                                        <i class="fa-solid {{ $sirkulasiTone['icon'] }}"></i>
                                        <span>{{ $dmSirkulasi }}</span>
                                    </div>
                                    <div class="dm-item-desc">
                                        Pengadukan otomatis (purging & stirring) tabung canister warna agar konsistensi pigmen merata sebelum dispensing.
                                    </div>
                                </div>
                            </div>

                            {{-- POINT 3: SOFTWARE KOMPUTER --}}
                            <div class="dm-item-card">
                                <div>
                                    <div class="dm-item-header">
                                        <div class="dm-item-title-group">
                                            <div class="dm-item-icon" style="background: {{ $softwareTone['bg'] }}; color: {{ $softwareTone['color'] }};">
                                                <i class="fa-solid fa-desktop"></i>
                                            </div>
                                            <div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Poin 3</span>
                                                <div class="dm-item-label">Sistem Operasi / PC</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dm-status-pill" style="background: {{ $softwareTone['bg'] }}; color: {{ $softwareTone['color'] }}; border-color: {{ $softwareTone['border'] }};">
                                        <i class="fa-solid {{ $softwareTone['icon'] }}"></i>
                                        <span>{{ $dmSoftware }}</span>
                                    </div>
                                    <div class="dm-item-desc">
                                        Kondisi perangkat keras komputer, respon sistem operasi, dan komunikasi kabel port COM/USB ke mesin tinting.
                                    </div>
                                </div>
                            </div>

                            {{-- POINT 4: PROGRAM MIX2WIN --}}
                            <div class="dm-item-card">
                                <div>
                                    <div class="dm-item-header">
                                        <div class="dm-item-title-group">
                                            <div class="dm-item-icon" style="background: {{ $mix2winTone['bg'] }}; color: {{ $mix2winTone['color'] }};">
                                                <i class="fa-solid fa-flask"></i>
                                            </div>
                                            <div>
                                                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Poin 4</span>
                                                <div class="dm-item-label">Program Formula Mix2Win</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dm-status-pill" style="background: {{ $mix2winTone['bg'] }}; color: {{ $mix2winTone['color'] }}; border-color: {{ $mix2winTone['border'] }};">
                                        <i class="fa-solid {{ $mix2winTone['icon'] }}"></i>
                                        <span>{{ $dmMix2win }}</span>
                                    </div>
                                    <div class="dm-item-desc">
                                        Aplikasi formulasi warna Dulux Mix2Win siap pakai, database formula update, dan siap melayani order tinting konsumen.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3. KESIMPULAN & REKOMENDASI MAINTENANCE --}}
                        <div class="dm-conclusion-card">
                            <div class="dm-conclusion-title">
                                <i class="fa-solid fa-clipboard-check" style="color: #0F52BA;"></i>
                                <span>Kesimpulan & Catatan Maintenance</span>
                            </div>
                            <p class="dm-conclusion-text">
                                {{ $dmKesimpulan ?: $machineHealth['desc'] }}
                            </p>
                        </div>
                    </div>

                    @if($textValues->isNotEmpty())
                        <div style="border-top: 1px solid var(--border-color); padding: 0.75rem 1.25rem 0.25rem 1.25rem;">
                            <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Parameter Tambahan</span>
                        </div>
                    @endif
                @else
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-list-check" style="color: #0F52BA;"></i>
                            <span>Isian & Data Formulir</span>
                        </div>
                        <span class="panel-count-badge">{{ $textValues->count() }} Parameter</span>
                    </div>
                @endif

                @if($textValues->isNotEmpty())
                    <table class="param-table">
                        <tbody>
                            @foreach($textValues as $val)
                                @php
                                    $fieldLabel = $val->formField?->field_label ?? ucwords(str_replace('_', ' ', (string)$val->field_name));
                                    $fieldType = $val->formField?->field_type ?? $val->field_type;
                                @endphp
                                <tr>
                                    <td class="param-num-col">
                                        <span class="param-num-circle">{{ $loop->iteration }}</span>
                                    </td>
                                    <td class="param-label-col">
                                        <div class="param-label-text">{{ $fieldLabel }}</div>
                                    </td>
                                    <td class="param-val-col">
                                        @php
                                            $isCompList = ($val->field_name === 'data_kompetitor_list' || ($val->formField && $val->formField->field_name === 'data_kompetitor_list'));
                                            $parsedCompList = null;
                                            if ($isCompList || (is_string($val->value_text) && str_starts_with(trim($val->value_text), '[{') && str_contains($val->value_text, 'harga_'))) {
                                                $parsedCompList = is_array($val->value_json) ? $val->value_json : json_decode($val->value_text, true);
                                            }
                                        @endphp
                                        @if(!empty($parsedCompList) && is_array($parsedCompList))
                                            <div style="display: flex; flex-direction: column; gap: 8px; margin: 4px 0;">
                                                @foreach($parsedCompList as $cIdx => $cItem)
                                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                                <span style="font-size: 0.72rem; font-weight: 800; background: #0F52BA; color: #fff; padding: 2px 6px; border-radius: 4px;">#{{ $cIdx + 1 }}</span>
                                                                <span style="font-size: 0.85rem; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px;">
                                                                    {{ $cItem['merk'] ?? $cItem['brand'] ?? 'Kompetitor' }}
                                                                </span>
                                                            </div>
                                                            @if(!empty($cItem['subbrand']))
                                                                <span style="font-size: 0.82rem; font-weight: 700; color: #334155;">
                                                                    {{ $cItem['subbrand'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.8rem;">
                                                            @if(isset($cItem['harga_galon']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;">
                                                                    <span style="color: #64748b; font-size: 0.75rem;">Galon:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_galon'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_galon'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                            @if(isset($cItem['harga_tin']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;">
                                                                    <span style="color: #64748b; font-size: 0.75rem;">Tin:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_tin'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_tin'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                            @if(isset($cItem['harga_pail']))
                                                                <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; display: inline-flex; gap: 4px; align-items: center;">
                                                                    <span style="color: #64748b; font-size: 0.75rem;">Pail:</span>
                                                                    <strong style="color: {{ (float)($cItem['harga_pail'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">
                                                                        Rp {{ number_format((float)($cItem['harga_pail'] ?? 0), 0, ',', '.') }}
                                                                    </strong>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($fieldType === 'currency' && $val->value_number !== null)
                                            <span class="val-currency">
                                                Rp {{ number_format((float)$val->value_number, 0, ',', '.') }}
                                            </span>
                                        @elseif($val->value_number !== null)
                                            <span class="val-number">
                                                {{ number_format((float)$val->value_number, (floor($val->value_number) == $val->value_number ? 0 : 2), ',', '.') }}
                                            </span>
                                        @elseif(!empty($val->value_json))
                                            <div class="val-chips-wrap">
                                                @foreach((array)$val->value_json as $chip)
                                                    <span class="val-chip">{{ $chip }}</span>
                                                @endforeach
                                            </div>
                                        @elseif(!empty($val->value_text))
                                            <span class="val-text">{{ $val->value_text }}</span>
                                        @else
                                            <span class="val-empty">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 2.5rem 1.5rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-file-lines" style="font-size: 2rem; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                        <p style="font-size: 0.88rem; margin: 0;">Tidak ada isian teks tambahan pada formulir ini.</p>
                    </div>
                @endif
            </div>

            {{-- PANEL 2: GALERI FOTO BUKTI / DOKUMENTASI --}}
            @if($mediaValues->isNotEmpty())
                <div class="panel-container">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-camera" style="color: #0F52BA;"></i>
                            <span>Foto Bukti & Dokumentasi</span>
                        </div>
                        <span class="panel-count-badge">{{ $mediaValues->count() }} Foto</span>
                    </div>

                    <div class="media-gallery-grid">
                        @foreach($mediaValues as $idx => $m)
                            <div class="media-item-card">
                                <div class="media-item-header">
                                    <span class="media-badge-tag"><i class="fa-solid fa-image"></i> Foto #{{ $loop->iteration }}</span>
                                    <div class="media-field-title">{{ $m['display_label'] ?? $m['label'] }}</div>
                                </div>
                                <div class="media-photo-frame" onclick="openPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['display_label'] ?? $m['label']) }}')" title="Klik untuk memperbesar">
                                    <img src="{{ $m['url'] }}" alt="{{ $m['label'] }}" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/475569?text=Gagal+Memuat+Foto';">
                                </div>
                                <div class="media-footer-bar">
                                    <button type="button" class="media-full-btn" onclick="openPhotoModal('{{ $m['url'] }}', '{{ addslashes($m['display_label'] ?? $m['label']) }}')">
                                        <i class="fa-solid fa-expand"></i> <span>Lihat Foto Penuh</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- LIGHTBOX MODAL UNTUK PREVIEW FOTO --}}
    <div id="photoLightbox" class="lightbox-backdrop" onclick="if(event.target === this) closePhotoModal()">
        <div class="lightbox-content-box">
            <div class="lightbox-header">
                <div class="lightbox-title">
                    <i class="fa-solid fa-image" style="color: #0F52BA;"></i>
                    <span id="lightboxTitle">Preview Foto Bukti</span>
                </div>
                <div class="lightbox-actions">
                    <a id="lightboxDownloadBtn" href="#" target="_blank" download class="lightbox-action-btn">
                        <i class="fa-solid fa-download"></i> <span>Unduh</span>
                    </a>
                    <button type="button" class="lightbox-close-btn" onclick="closePhotoModal()">&times;</button>
                </div>
            </div>
            <div class="lightbox-image-wrap">
                <img id="lightboxImg" src="" alt="Preview">
            </div>
        </div>
    </div>

    {{-- MODAL REJECT WITH REASON --}}
    <div id="rejectModal" class="custom-modal-backdrop" onclick="if(event.target === this) closeRejectModal()">
        <div class="custom-modal-box">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #b91c1c; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Tolak Laporan Ini?</span>
                </h3>
                <button type="button" onclick="closeRejectModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form action="{{ route('portal.report.submission.status', ['code' => $template->code, 'id' => $submission->id, 'p' => $tenantPrincipal->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.35rem;">
                        Alasan / Catatan Penolakan (Opsional):
                    </label>
                    <textarea name="verification_notes" rows="3" placeholder="Tuliskan alasan penolakan atau catatan evaluasi untuk promotor..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">{{ $submission->verification_notes }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" onclick="closeRejectModal()" class="btn-portal-back">Batal</button>
                    <button type="submit" class="btn-action-reject">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Konfirmasi Tolak</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openPhotoModal(imageUrl, title) {
            document.getElementById('lightboxImg').src = imageUrl;
            document.getElementById('lightboxTitle').textContent = title || 'Preview Foto Bukti';
            document.getElementById('lightboxDownloadBtn').href = imageUrl;
            document.getElementById('photoLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closePhotoModal() {
            document.getElementById('photoLightbox').style.display = 'none';
            document.getElementById('lightboxImg').src = '';
            document.body.style.overflow = 'auto';
        }

        function openRejectModal() {
            document.getElementById('rejectModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePhotoModal();
                closeRejectModal();
            }
        });
    </script>
    @endpush
@endsection
