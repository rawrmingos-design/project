<style>
    :root {
        font-size: 13.5px;
    }

    .fi-body {
        font-family: 'Poppins', sans-serif;
    }

    .fi-form-actions.settings-form-actions {
        margin-top: 1rem;
        padding-top: 0.25rem;
    }

    .fi-form-actions.settings-form-actions .fi-btn {
        min-height: 2.5rem;
    }

    .fil-admin-2fa-alert {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.9rem;
        width: min(100%, 56rem);
        margin-inline-end: 0.35rem;
        padding: 0.62rem 0.9rem;
        border-radius: 0.65rem;
        border: 1px solid rgba(245, 158, 11, 0.38);
        background: linear-gradient(180deg, rgba(255, 251, 235, 0.98) 0%, rgba(255, 247, 237, 0.98) 100%);
        color: #b45309;
        box-shadow: 0 8px 20px rgba(146, 64, 14, 0.14);
    }

    .fil-admin-2fa-alert__content {
        display: flex;
        align-items: center;
        gap: 0.68rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .fil-admin-2fa-alert__icon-wrap {
        width: 1.2rem;
        height: 1.2rem;
        flex: 0 0 1.2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .fil-admin-2fa-alert__icon {
        width: 1.08rem;
        height: 1.08rem;
        color: #d97706;
    }

    .fil-admin-2fa-alert__text {
        font-size: 0.9rem;
        line-height: 1.38;
        font-weight: 600;
        color: #b45309;
    }

    .fil-admin-2fa-alert__action {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        text-decoration: none;
        border-bottom: 1px solid rgba(180, 83, 9, 0.65);
        color: #92400e;
        font-size: 0.86rem;
        line-height: 1.1;
        font-weight: 700;
        padding-bottom: 1px;
        transition: color 0.16s ease, border-color 0.16s ease;
    }

    .fil-admin-2fa-alert__action:hover,
    .fil-admin-2fa-alert__action:focus-visible {
        color: #7c2d12;
        border-color: rgba(124, 45, 18, 0.85);
        outline: none;
    }

    @media (max-width: 1280px) {
        .fil-admin-2fa-alert {
            width: min(100%, 48rem);
            padding: 0.55rem 0.75rem;
            gap: 0.65rem;
        }

        .fil-admin-2fa-alert__text {
            font-size: 0.84rem;
        }

        .fil-admin-2fa-alert__action {
            font-size: 0.81rem;
        }
    }

    @media (max-width: 960px) {
        .fil-admin-2fa-alert {
            display: none;
        }
    }
</style>
