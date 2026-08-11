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
  usePage: () => ({ props: {} }),
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
      data: {
        ...initialData,
        business_name: 'Test Shop',
        business_url: 'https://example.com',
      },
      setData: jest.fn(),
      post: jest.fn(),
      processing: false,
      errors: {},
    }));
  });

  const setProcessingForm = () => {
    mockUseForm.mockImplementation((initialData = {}) => ({
      data: {
        ...initialData,
        business_name: 'Test Shop',
        business_url: 'https://example.com',
      },
      setData: jest.fn(),
      post: jest.fn(),
      processing: true,
      errors: {},
    }));
  };

  const validFormData = (initialData = {}) => ({
    ...initialData,
    business_name: 'Test Shop',
    business_url: 'https://example.com',
  });

  const advanceToStep2 = async () => {
    await userEvent.click(screen.getByRole('button', { name: /Lanjut ke Upload Dokumen/i }));
  };

  describe('Account Info Banner', () => {
    it('renders account information banner', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText('Akun Saat Ini')).toBeInTheDocument();
      expect(screen.getByText('testuser')).toBeInTheDocument();
      expect(screen.getByText('test@example.com')).toBeInTheDocument();
      expect(screen.getByText('Member')).toBeInTheDocument();
    });

    it('displays account information message', () => {
      render(<Registry {...defaultProps} />);

      expect(screen.getByText(/Informasi akun yang akan digunakan/i)).toBeInTheDocument();
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

    it('renders reCAPTCHA explicitly after advancing to Step 2', async () => {
      render(<Registry {...defaultProps} />);

      expect(document.querySelector('.g-recaptcha')).not.toBeInTheDocument();
      await advanceToStep2();

      await waitFor(() => expect(window.grecaptcha.render).toHaveBeenCalledWith(
        expect.any(HTMLElement),
        expect.objectContaining({
          sitekey: 'test-site-key',
          theme: 'dark',
          callback: expect.any(Function),
          'expired-callback': expect.any(Function),
        })
      ));
    });

    it('renders one widget per Step 2 mount', async () => {
      render(<Registry {...defaultProps} />);
      await advanceToStep2();
      await waitFor(() => expect(window.grecaptcha.render).toHaveBeenCalledTimes(1));

      await userEvent.click(screen.getByRole('button', { name: /Kembali/i }));
      await userEvent.click(screen.getByRole('button', { name: /Lanjut ke Upload Dokumen/i }));
      await waitFor(() => expect(window.grecaptcha.render).toHaveBeenCalledTimes(2));
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
      expect(window.grecaptcha.render).not.toHaveBeenCalled();
    });

    it('resets the rendered widget by ID after a CAPTCHA expiry', async () => {
      render(<Registry {...defaultProps} />);
      await advanceToStep2();
      await waitFor(() => expect(window.grecaptcha.render).toHaveBeenCalled());

      window.onResellerCaptchaExpired();
      expect(window.grecaptcha.reset).toHaveBeenCalledWith(0);
    });
  });

  describe('Form Fields', () => {
    it('renders all required form fields', () => {
      mockUseForm.mockImplementation((initialData = {}) => ({
        data: {
          ...initialData,
          business_name: 'Test Shop',
          business_url: 'https://example.com',
        },
        setData: jest.fn(),
        post: jest.fn(),
        processing: false,
        errors: {},
      }));
      render(<Registry {...defaultProps} />);

      expect(screen.getByPlaceholderText(/Masukkan nama bisnis Anda/i)).toBeInTheDocument();
      expect(screen.getByPlaceholderText(/https:\/\//i)).toBeInTheDocument();
      expect(screen.getByPlaceholderText(/^0$/i)).toBeInTheDocument();
      expect(screen.getByPlaceholderText(/Ceritakan singkat/i)).toBeInTheDocument();
    });

    it('renders document upload sections', async () => {
      render(<Registry {...defaultProps} />);
      await advanceToStep2();

      expect(screen.getByText(/Foto KTP Asli/i)).toBeInTheDocument();
      expect(screen.getByText(/Foto Selfie dengan KTP/i)).toBeInTheDocument();
      expect(screen.getByText(/Bukti Kepemilikan Toko/i)).toBeInTheDocument();
    });

    it('displays submit button', async () => {
      render(<Registry {...defaultProps} />);
      await advanceToStep2();

      const submitButton = screen.getByRole('button', { name: /Kirim Aplikasi/i });
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
    it('displays error for captcha validation', async () => {
      const propsWithErrors = {
        ...defaultProps,
      };
      
      const { rerender } = render(<Registry {...propsWithErrors} />);
      await advanceToStep2();

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

      mockUseForm.mockImplementation((initialData = {}) => ({
        data: initialData,
        setData: jest.fn(),
        post: jest.fn(),
        processing: false,
        errors: {},
      }));
      render(<Registry {...propsWithExisting} />);

      expect(screen.getByDisplayValue('Existing Business')).toBeInTheDocument();
      expect(screen.getByDisplayValue('https://existing.com')).toBeInTheDocument();
      expect(screen.getByDisplayValue('100000000')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Existing reason')).toBeInTheDocument();
    });
  });

  describe('Form Submission', () => {
    it('disables submit button when processing', async () => {
      setProcessingForm();

      render(<Registry {...defaultProps} />);

      await advanceToStep2();
      const submitButton = screen.getByRole('button', { name: /Mengirim/i });
      expect(submitButton).toBeDisabled();
    });

    it('shows processing state text', async () => {
      setProcessingForm();

      render(<Registry {...defaultProps} />);
      await advanceToStep2();

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

      expect(screen.getByText(/NAMA TOKO\/BISNIS/i)).toBeInTheDocument();
      expect(screen.getByText(/LINK PLATFORM/i)).toBeInTheDocument();
      expect(screen.getByText(/ESTIMASI TRANSAKSI PER BULAN/i)).toBeInTheDocument();
    });
  });

  describe('Security Features', () => {
    it('renders the registration security requirements', () => {
      const props = { ...defaultProps, current_user: null };
      render(<Registry {...props} />);

      expect(screen.getByText(/Harus login sebagai/i)).toBeInTheDocument();
      expect(screen.getByText(/Memiliki informasi bisnis yang valid/i)).toBeInTheDocument();
      expect(screen.getByText(/Menyelesaikan verifikasi captcha/i)).toBeInTheDocument();
    });
  });
});
