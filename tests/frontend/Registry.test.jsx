import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { router } from '@inertiajs/react';
import Registry from '../../resources/js/public/Pages/Reseller/Registry';

const mockUseForm = jest.fn();

// Mock Inertia
jest.mock('@inertiajs/react', () => ({
  Head: ({ children }) => <>{children}</>,
  useForm: (initialData = {}) => mockUseForm(initialData),
  router: {
    post: jest.fn(),
  },
}));

describe('Registry Component', () => {
  const defaultProps = {
    current_user: {
      username: 'testuser',
      email: 'test@example.com',
      phone: '081234567890',
      role: 'Member',
    },
    captcha: {
      site_key: 'test-site-key',
      enabled: true,
    },
    existing_application: null,
    existing_documents: {
      identity: null,
      selfie: null,
      business_proof: null,
    },
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseForm.mockImplementation((initialData = {}) => ({
      data: initialData,
      setData: jest.fn(),
      post: jest.fn(),
      processing: false,
      errors: {},
    }));
  });

  describe('Account Info Banner', () => {
    it('renders account information banner', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText('Akun Yang Akan Diupgrade')).toBeInTheDocument();
      expect(screen.getByText('testuser')).toBeInTheDocument();
      expect(screen.getByText('test@example.com')).toBeInTheDocument();
      expect(screen.getByText('Member')).toBeInTheDocument();
    });

    it('displays upgrade explanation message', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText(/Setelah pengajuan disetujui/i)).toBeInTheDocument();
      expect(screen.getByText(/username dan password yang sama/i)).toBeInTheDocument();
    });

    it('does not render banner when current_user is null', () => {
      const props = { ...defaultProps, current_user: null };
      render(<Registry {...props} />);

      expect(screen.queryByText('Akun Yang Akan Diupgrade')).not.toBeInTheDocument();
    });
  });

  describe('reCAPTCHA Integration', () => {
    it('renders reCAPTCHA script when enabled', () => {
      render(<Registry {...defaultProps} />);

      const scripts = document.querySelectorAll('script');
      const recaptchaScript = Array.from(scripts).find(
        script => script.src && script.src.includes('recaptcha/api.js')
      );

      expect(recaptchaScript).toBeDefined();
    });

    it('renders reCAPTCHA widget when enabled', () => {
      render(<Registry {...defaultProps} />);

      const recaptchaWidget = document.querySelector('.g-recaptcha');
      expect(recaptchaWidget).toBeInTheDocument();
      expect(recaptchaWidget).toHaveAttribute('data-sitekey', 'test-site-key');
    });

    it('does not render reCAPTCHA when disabled', () => {
      const props = {
        ...defaultProps,
        captcha: { site_key: 'test-key', enabled: false },
      };
      render(<Registry {...props} />);

      const recaptchaWidget = document.querySelector('.g-recaptcha');
      expect(recaptchaWidget).not.toBeInTheDocument();
    });

    it('does not render reCAPTCHA when site_key is missing', () => {
      const props = {
        ...defaultProps,
        captcha: { site_key: null, enabled: true },
      };
      render(<Registry {...props} />);

      const recaptchaWidget = document.querySelector('.g-recaptcha');
      expect(recaptchaWidget).not.toBeInTheDocument();
    });
  });

  describe('Form Fields', () => {
    it('renders all required form fields', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByPlaceholderText(/PT Digital Solusi/i)).toBeInTheDocument();
      expect(screen.getByPlaceholderText(/https:\/\//i)).toBeInTheDocument();
      expect(screen.getByPlaceholderText(/50000000/i)).toBeInTheDocument();
      expect(screen.getByPlaceholderText(/Ceritakan mengapa/i)).toBeInTheDocument();
    });

    it('renders document upload sections', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText(/Identitas \(KTP\/ID Card\)/i)).toBeInTheDocument();
      expect(screen.getByText(/Selfie dengan KTP/i)).toBeInTheDocument();
      expect(screen.getByText(/Bukti Bisnis/i)).toBeInTheDocument();
    });

    it('displays submit button', () => {
      render(<Registry {...defaultProps} />);

      const submitButton = screen.getByRole('button', { name: /Kirim Pengajuan/i });
      expect(submitButton).toBeInTheDocument();
      expect(submitButton).not.toBeDisabled();
    });

    it('displays cancel button', () => {
      render(<Registry {...defaultProps} />);

      const cancelButton = screen.getByRole('button', { name: /Batal/i });
      expect(cancelButton).toBeInTheDocument();
    });
  });

  describe('Form Validation', () => {
    it('displays error for captcha validation', () => {
      const propsWithErrors = {
        ...defaultProps,
      };
      
      const { rerender } = render(<Registry {...propsWithErrors} />);

      mockUseForm.mockImplementation((initialData = {}) => ({
        data: initialData,
        setData: jest.fn(),
        post: jest.fn(),
        processing: false,
        errors: { 'g-recaptcha-response': 'Captcha wajib diverifikasi.' },
      }));

      rerender(<Registry {...propsWithErrors} />);

      expect(screen.getByText('Captcha wajib diverifikasi.')).toBeInTheDocument();
    });
  });

  describe('Existing Application Data', () => {
    it('pre-fills form with existing application data', () => {
      const propsWithExisting = {
        ...defaultProps,
        existing_application: {
          business_name: 'Existing Business',
          business_url: 'https://existing.com',
          estimated_transactions: '100000000',
          application_reason: 'Existing reason',
          status: 'rejected',
        },
      };

      render(<Registry {...propsWithExisting} />);

      expect(screen.getByDisplayValue('Existing Business')).toBeInTheDocument();
      expect(screen.getByDisplayValue('https://existing.com')).toBeInTheDocument();
      expect(screen.getByDisplayValue('100000000')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Existing reason')).toBeInTheDocument();
    });
  });

  describe('Form Submission', () => {
    it('disables submit button when processing', () => {
      mockUseForm.mockImplementation((initialData = {}) => ({
        data: initialData,
        setData: jest.fn(),
        post: jest.fn(),
        processing: true,
        errors: {},
      }));

      render(<Registry {...defaultProps} />);

      const submitButton = screen.getByRole('button', { name: /Mengirim/i });
      expect(submitButton).toBeDisabled();
    });

    it('shows processing state text', () => {
      mockUseForm.mockImplementation((initialData = {}) => ({
        data: initialData,
        setData: jest.fn(),
        post: jest.fn(),
        processing: true,
        errors: {},
      }));

      render(<Registry {...defaultProps} />);

      expect(screen.getByText('Mengirim...')).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    it('has proper heading hierarchy', () => {
      render(<Registry {...defaultProps} />);

      const mainHeading = screen.getByRole('heading', { name: /Daftar Sebagai Reseller/i });
      expect(mainHeading).toBeInTheDocument();
    });

    it('has proper labels for form inputs', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText(/NAMA BISNIS/i)).toBeInTheDocument();
      expect(screen.getByText(/URL\/WEBSITE BISNIS/i)).toBeInTheDocument();
      expect(screen.getByText(/ESTIMASI TRANSAKSI BULANAN/i)).toBeInTheDocument();
    });
  });

  describe('Security Features', () => {
    it('renders benefit badges including security features', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText(/API H2H/i)).toBeInTheDocument();
      expect(screen.getByText(/Harga Khusus/i)).toBeInTheDocument();
      expect(screen.getByText(/Support 24\/7/i)).toBeInTheDocument();
    });
  });
});
