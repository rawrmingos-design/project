import React, { useMemo } from 'react';
import { Autoplay, EffectFade, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/pagination';

const meteorField = [
    { left: 1130, delay: 0.686975, duration: 8 },
    { left: -350, delay: 0.670151, duration: 8 },
    { left: 563, delay: 0.632454, duration: 9 },
    { left: -969, delay: 0.524996, duration: 5 },
    { left: -1153, delay: 0.460272, duration: 8 },
    { left: -560, delay: 0.223791, duration: 6 },
    { left: -1287, delay: 0.406558, duration: 4 },
    { left: 211, delay: 0.475533, duration: 6 },
    { left: -63, delay: 0.394929, duration: 5 },
    { left: -112, delay: 0.78249, duration: 2 },
    { left: 946, delay: 0.353787, duration: 5 },
    { left: 275, delay: 0.309607, duration: 5 },
    { left: 1216, delay: 0.35162, duration: 8 },
    { left: -210, delay: 0.413144, duration: 7 },
    { left: -842, delay: 0.395388, duration: 6 },
    { left: -323, delay: 0.582248, duration: 4 },
    { left: 278, delay: 0.710367, duration: 4 },
    { left: -736, delay: 0.564896, duration: 6 },
    { left: -800, delay: 0.206357, duration: 7 },
    { left: -1118, delay: 0.628613, duration: 9 },
    { left: 1361, delay: 0.529785, duration: 7 },
    { left: -11, delay: 0.64863, duration: 6 },
    { left: -678, delay: 0.701722, duration: 3 },
    { left: -170, delay: 0.366231, duration: 5 },
    { left: 946, delay: 0.521904, duration: 7 },
    { left: 1364, delay: 0.484818, duration: 9 },
    { left: 943, delay: 0.502043, duration: 3 },
    { left: 1296, delay: 0.577243, duration: 7 },
    { left: 1273, delay: 0.273317, duration: 5 },
    { left: -1306, delay: 0.556245, duration: 7 },
    { left: -360, delay: 0.344508, duration: 5 },
    { left: 306, delay: 0.332693, duration: 6 },
    { left: 312, delay: 0.250245, duration: 9 },
    { left: 649, delay: 0.607517, duration: 2 },
    { left: 13, delay: 0.379304, duration: 6 },
    { left: 1269, delay: 0.586079, duration: 5 },
    { left: -798, delay: 0.675148, duration: 4 },
    { left: 1199, delay: 0.515393, duration: 6 },
    { left: 304, delay: 0.799655, duration: 8 },
];

export default function HeroBanner({ banners = [] }) {
    const safeBanners = useMemo(() => (banners.length ? banners : [{
        id: 'fallback',
        title: 'Top Up Game Lebih Cepat',
        description: 'Pilih game favoritmu, masukkan data akun, lalu selesaikan pembayaran dengan cepat.',
        image: '/assets/logo/favicon.webp',
    }]), [banners]);

    return (
        <section className="hero hero--storefront hero-storefront__section">
            <div className="hero-storefront__backdrop" aria-hidden="true">
                <div className="hero-storefront__gradient" />
                <div className="hero-storefront__grid" />
                <div className="hero-storefront__meteors">
                    {meteorField.map((meteor, index) => (
                        <span
                            key={`${meteor.left}-${meteor.delay}-${index}`}
                            className="hero-storefront__meteor"
                            style={{
                                top: '-20px',
                                left: `${meteor.left}px`,
                                animationDelay: `${meteor.delay}s`,
                                animationDuration: `${meteor.duration}s`,
                            }}
                        />
                    ))}
                </div>
            </div>

            <div className="hero-storefront__container hero-storefront__frame">
                <Swiper
                    className="hero-swiper hero-swiper--storefront swiper hero__visual hero__visual--banner hero-swiper-react"
                    modules={[Autoplay, EffectFade, Pagination]}
                    loop={safeBanners.length > 1}
                    speed={1100}
                    slidesPerView={1}
                    spaceBetween={0}
                    centeredSlides
                    effect="fade"
                    fadeEffect={{ crossFade: true }}
                    pagination={safeBanners.length > 1 ? { clickable: true } : false}
                    autoplay={safeBanners.length > 1 ? { delay: 5200, disableOnInteraction: false, pauseOnMouseEnter: true } : false}
                >
                    {safeBanners.map((banner, index) => (
                        <SwiperSlide key={banner.id ?? index}>
                            <img
                                className="hero-swiper-react__image"
                                src={banner.image}
                                alt={banner.title || `Banner ${index + 1}`}
                                loading={index === 0 ? 'eager' : 'lazy'}
                                decoding={index === 0 ? 'sync' : 'async'}
                            />
                        </SwiperSlide>
                    ))}
                </Swiper>
            </div>
        </section>
    );
}
