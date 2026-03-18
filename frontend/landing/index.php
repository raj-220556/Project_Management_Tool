<?php
// frontend/landing/index.php
require_once __DIR__ . '/../../backend/shared/includes/init.php';
if (isLoggedIn())
  goToDashboard(currentUser()['role']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SprintDesk — Agile Project Management</title>
  <!-- NEW FONTS: Plus Jakarta Sans + DM Mono -->
  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --brand: #6366F1;
      --brand-d: #4F46E5;
      --brand-l: #818CF8;
      --green: #10B981;
      --amber: #F59E0B;
      --red: #EF4444;
      --blue: #3B82F6;
      --bg: #F8F7FF;
      --surface: #FFFFFF;
      --surface2: #F1F0FF;
      --border: #E4E2FF;
      --border2: #C7C5F5;
      --text: #1A1744;
      --text2: #4B4880;
      --text3: #9896CC;
      --sidebar: #1A1744;
      --fh: 'Plus Jakarta Sans', sans-serif;
      --fb: 'DM Sans', sans-serif;
      --fm: 'DM Mono', monospace;
      --r: 8px;
      --r2: 14px;
      --r3: 20px;
      --rf: 9999px;
      --sh-sm: 0 1px 4px rgba(99, 102, 241, .06);
      --sh-md: 0 6px 22px rgba(99, 102, 241, .1);
      --sh-lg: 0 20px 60px rgba(99, 102, 241, .14);
      --sh-brand: 0 6px 22px rgba(99, 102, 241, .35);
    }

    [data-theme=dark] {
      --bg: #0B0A1E;
      --surface: #110F2A;
      --surface2: #191630;
      --border: #252245;
      --border2: #38347A;
      --text: #E8E6FF;
      --text2: #6D6AA8;
      --text3: #38356A;
    }

    *,
    ::before,
    ::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    html {
      scroll-behavior: smooth
    }

    body {
      font-family: var(--fb);
      background: var(--bg);
      color: var(--text);
      overflow-x: hidden;
      line-height: 1.6;
      transition: background .35s, color .35s;
      font-weight: 500;
      -webkit-font-smoothing: antialiased
    }

    h1,
    h2,
    h3,
    h4 {
      font-family: var(--fh);
      line-height: 1.15;
      font-weight: 800;
      letter-spacing: -0.03em
    }

    /* NEW PREMIUM TABLES */
    .price-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-top: 54px
    }

    .pcard {
      background: var(--surface);
      border-radius: 24px;
      padding: 40px 32px;
      border: 1px solid var(--border);
      transition: all .3s;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column
    }

    .pcard.popular {
      border-color: var(--brand);
      box-shadow: var(--sh-brand)
    }

    .pcard.popular::before {
      content: 'Popular';
      position: absolute;
      top: 12px;
      right: -30px;
      background: var(--brand);
      color: #fff;
      font-size: 10px;
      font-weight: 800;
      padding: 4px 35px;
      transform: rotate(45deg);
      text-transform: uppercase;
      letter-spacing: 1px
    }

    .pcard-name {
      font-size: 14px;
      font-weight: 700;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 12px
    }

    .pcard-price {
      font-size: 48px;
      font-weight: 800;
      margin-bottom: 8px;
      display: flex;
      align-items: baseline;
      gap: 4px
    }

    .pcard-price span {
      font-size: 16px;
      color: var(--text3);
      font-weight: 500
    }

    .pcard-desc {
      font-size: 14px;
      color: var(--text2);
      margin-bottom: 32px;
      height: 44px
    }

    .pcard-feats {
      list-style: none;
      margin-bottom: 32px;
      flex: 1
    }

    .pcard-feats li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--text);
      margin-bottom: 12px
    }

    .pcard-feats li::before {
      content: '✓';
      color: var(--brand);
      font-weight: 800
    }

    .btn-price {
      width: 100%;
      text-align: center;
      justify-content: center;
      padding: 14px
    }

    a {
      text-decoration: none;
      color: inherit
    }

    button {
      cursor: pointer;
      font-family: var(--fb)
    }

    /* CURTAIN */
    #sd-curtain {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: linear-gradient(135deg, var(--brand-d), var(--brand));
      transform: translateY(100%);
      pointer-events: none;
      transition: transform .42s cubic-bezier(.76, 0, .24, 1)
    }

    #sd-curtain.go {
      transform: translateY(0);
      pointer-events: all
    }

    /* NAV */
    nav {
      position: fixed;
      top: 0;
      inset-inline: 0;
      z-index: 500;
      padding: 16px 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all .3s
    }

    nav.stuck {
      background: rgba(248, 247, 255, .94);
      backdrop-filter: blur(18px);
      box-shadow: 0 1px 0 var(--border)
    }

    [data-theme=dark] nav.stuck {
      background: rgba(11, 10, 30, .94)
    }

    .logo {
      font-family: var(--fh);
      font-size: 20px;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .logo-mark {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--brand), var(--brand-d));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      box-shadow: var(--sh-brand);
      flex-shrink: 0
    }

    .logo em {
      color: var(--brand);
      font-style: normal
    }

    .nav-mid {
      display: flex;
      gap: 28px
    }

    .nav-mid a {
      font-size: 14px;
      font-weight: 500;
      color: var(--text3);
      transition: color .2s
    }

    .nav-mid a:hover {
      color: var(--text)
    }

    .nav-end {
      display: flex;
      align-items: center;
      gap: 10px
    }

    /* BUTTONS */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 20px;
      border-radius: var(--r2);
      font-size: 13.5px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all .2s;
      font-family: var(--fh);
      text-decoration: none;
      letter-spacing: -.01em
    }

    .btn-ghost {
      background: var(--surface);
      border: 1.5px solid var(--border);
      color: var(--text)
    }

    .btn-ghost:hover {
      border-color: var(--brand);
      color: var(--brand)
    }

    .btn-solid {
      background: linear-gradient(135deg, var(--brand), var(--brand-d));
      color: #fff;
      box-shadow: var(--sh-brand)
    }

    .btn-solid:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(99, 102, 241, .45);
      color: #fff
    }

    .btn-lg {
      padding: 13px 30px;
      font-size: 15px;
      border-radius: var(--r3);
      font-weight: 700
    }

    .btn-outline-white {
      background: rgba(255, 255, 255, .12);
      border: 1.5px solid rgba(255, 255, 255, .3);
      color: #fff;
      padding: 13px 28px;
      font-size: 15px;
      border-radius: var(--r3);
      font-weight: 600;
      backdrop-filter: blur(6px)
    }

    .btn-outline-white:hover {
      background: rgba(255, 255, 255, .2);
      color: #fff
    }

    .theme-btn {
      width: 36px;
      height: 36px;
      border-radius: var(--r2);
      background: var(--surface);
      border: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      transition: all .2s
    }

    .theme-btn:hover {
      border-color: var(--brand)
    }

    /* HERO */
    .hero-wrap {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: center;
      gap: 56px;
      padding: 130px 56px 80px;
      max-width: 1280px;
      margin: 0 auto;
      position: relative
    }

    .hero-bg-fixed {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: -1
    }

    .hblob {
      position: absolute;
      border-radius: 50%;
      filter: blur(100px);
      animation: hbf 12s ease-in-out infinite
    }

    .hb1 {
      width: 640px;
      height: 640px;
      background: rgba(99, 102, 241, .1);
      top: -180px;
      right: -120px
    }

    .hb2 {
      width: 480px;
      height: 480px;
      background: rgba(16, 185, 129, .07);
      bottom: -80px;
      left: -80px;
      animation-delay: -6s
    }

    .hb3 {
      width: 360px;
      height: 360px;
      background: rgba(245, 158, 11, .06);
      top: 35%;
      left: 38%;
      animation-delay: -9s
    }

    [data-theme=dark] .hblob {
      opacity: .45
    }

    @keyframes hbf {

      0%,
      100% {
        transform: scale(1) translate(0, 0)
      }

      50% {
        transform: scale(1.07) translate(18px, -14px)
      }
    }

    .hero-grid-bg {
      position: fixed;
      inset: 0;
      background-image: linear-gradient(rgba(99, 102, 241, .035) 1px, transparent 1px), linear-gradient(90deg, rgba(99, 102, 241, .035) 1px, transparent 1px);
      background-size: 54px 54px;
      pointer-events: none;
      z-index: -1
    }

    /* HERO LEFT */
    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 5px 14px;
      border-radius: var(--rf);
      background: rgba(99, 102, 241, .1);
      border: 1px solid rgba(99, 102, 241, .22);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .8px;
      text-transform: uppercase;
      color: var(--brand);
      margin-bottom: 22px;
      font-family: var(--fh)
    }

    .eyebrow-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--brand);
      animation: ep 2s infinite;
      flex-shrink: 0
    }

    @keyframes ep {

      0%,
      100% {
        opacity: 1;
        transform: scale(1)
      }

      50% {
        opacity: .35;
        transform: scale(1.7)
      }
    }

    .hero-h1 {
      font-size: clamp(34px, 4.8vw, 62px);
      font-weight: 800;
      line-height: 1.08;
      margin-bottom: 20px;
      letter-spacing: -.02em;
      animation: fup .55s .05s both
    }

    .hero-h1 .grad {
      background: linear-gradient(135deg, var(--brand), var(--brand-l));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text
    }

    @keyframes fup {
      from {
        opacity: 0;
        transform: translateY(20px)
      }

      to {
        opacity: 1;
        transform: none
      }
    }

    .hero-p {
      font-size: 16.5px;
      color: var(--text2);
      line-height: 1.72;
      max-width: 490px;
      margin-bottom: 34px;
      animation: fup .55s .14s both;
      font-weight: 400
    }

    .hero-btns {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      animation: fup .55s .23s both
    }

    .hero-note {
      font-size: 12px;
      color: var(--text3);
      margin-top: 14px;
      animation: fup .55s .32s both;
      font-family: var(--fh)
    }

    /* HERO MOCKUP */
    .hero-right {
      animation: fright .65s .18s both
    }

    @keyframes fright {
      from {
        opacity: 0;
        transform: translateX(28px)
      }

      to {
        opacity: 1;
        transform: none
      }
    }

    .mock-shell {
      background: var(--surface);
      border-radius: 20px;
      border: 1px solid var(--border);
      box-shadow: var(--sh-lg);
      overflow: hidden;
      transform: perspective(1000px) rotateY(-7deg) rotateX(2deg);
      transition: transform .4s
    }

    .mock-shell:hover {
      transform: perspective(1000px) rotateY(-2deg) rotateX(0deg)
    }

    .mock-bar {
      background: var(--surface2);
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 7px;
      border-bottom: 1px solid var(--border)
    }

    .mdot {
      width: 10px;
      height: 10px;
      border-radius: 50%
    }

    .mock-url-b {
      flex: 1;
      background: var(--surface);
      border-radius: 5px;
      padding: 3px 10px;
      font-size: 10px;
      font-family: var(--fm);
      color: var(--text3);
      margin: 0 8px;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis
    }

    .mock-body {
      padding: 14px
    }

    .mock-sec-hd {
      font-family: var(--fh);
      font-size: 10px;
      font-weight: 700;
      color: var(--text2);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .mock-sprint-pill {
      font-size: 8px;
      padding: 2px 7px;
      border-radius: var(--rf);
      background: rgba(99, 102, 241, .12);
      color: var(--brand);
      font-weight: 700;
      font-family: var(--fh)
    }

    .mock-cols {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 7px
    }

    .mock-col-b {
      background: var(--surface2);
      border-radius: 8px;
      padding: 8px
    }

    .mock-col-hd {
      font-size: 8px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .9px;
      color: var(--text3);
      margin-bottom: 6px;
      display: flex;
      justify-content: space-between;
      font-family: var(--fh)
    }

    .mc {
      background: var(--surface);
      border-radius: 6px;
      padding: 7px;
      margin-bottom: 5px;
      border: 1px solid var(--border)
    }

    .mc-t {
      font-size: 8.5px;
      color: var(--text);
      margin-bottom: 4px;
      font-weight: 500
    }

    .mc-r {
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .mchip {
      font-size: 7px;
      padding: 1px 5px;
      border-radius: var(--rf);
      font-weight: 700;
      font-family: var(--fh)
    }

    .mc-todo {
      background: rgba(148, 150, 204, .14);
      color: var(--text2)
    }

    .mc-ip {
      background: rgba(245, 158, 11, .16);
      color: #92580A
    }

    .mc-done {
      background: rgba(16, 185, 129, .14);
      color: #047857
    }

    .mava {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--brand), var(--green));
      flex-shrink: 0
    }

    .mock-float {
      position: absolute;
      background: var(--surface);
      border-radius: 10px;
      padding: 9px 13px;
      border: 1px solid var(--border);
      box-shadow: var(--sh-md);
      font-size: 11px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 7px;
      white-space: nowrap;
      z-index: 2;
      font-family: var(--fh)
    }

    .mf1 {
      bottom: -14px;
      left: -28px;
      animation: bob 3.4s ease-in-out infinite
    }

    .mf2 {
      top: 16px;
      right: -22px;
      animation: bob 3.4s 1.8s ease-in-out infinite
    }

    @keyframes bob {

      0%,
      100% {
        transform: translateY(0)
      }

      50% {
        transform: translateY(-8px)
      }
    }

    /* STATS */
    .stats-row {
      background: var(--surface);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 36px 56px
    }

    .stats-inner {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 56px;
      flex-wrap: wrap;
      max-width: 880px;
      margin: 0 auto
    }

    .snum {
      font-family: var(--fh);
      font-size: 34px;
      font-weight: 800;
      color: var(--brand);
      line-height: 1
    }

    .slbl {
      font-size: 12px;
      color: var(--text3);
      margin-top: 4px;
      font-weight: 500
    }

    .sdiv {
      width: 1px;
      height: 44px;
      background: var(--border)
    }

    /* SECTIONS */
    .section {
      padding: 96px 56px
    }

    .sec-inner {
      max-width: 1100px;
      margin: 0 auto
    }

    .sec-tag {
      font-size: 10.5px;
      font-weight: 700;
      letter-spacing: 1.8px;
      text-transform: uppercase;
      color: var(--brand);
      margin-bottom: 12px;
      font-family: var(--fh)
    }

    .sec-h {
      font-size: clamp(24px, 3.8vw, 44px);
      font-weight: 800;
      line-height: 1.18;
      margin-bottom: 14px;
      letter-spacing: -.02em
    }

    .sec-p {
      font-size: 16px;
      color: var(--text2);
      line-height: 1.68;
      max-width: 460px
    }

    /* ============================================================ */
    /* SLIDESHOW DEMO                                               */
    /* ============================================================ */
    .demo-section {
      padding: 96px 56px;
      background: var(--surface)
    }

    .demo-inner {
      max-width: 1060px;
      margin: 0 auto
    }

    /* Slide selector tabs */
    .slide-tabs {
      display: flex;
      gap: 0;
      margin-top: 28px;
      border-radius: var(--r3);
      overflow: hidden;
      border: 1.5px solid var(--border);
      background: var(--surface2)
    }

    .slide-tab {
      flex: 1;
      padding: 13px 10px;
      font-size: 13px;
      font-weight: 600;
      font-family: var(--fh);
      border: none;
      background: none;
      color: var(--text3);
      cursor: pointer;
      transition: all .22s;
      text-align: center;
      border-right: 1px solid var(--border);
      position: relative
    }

    .slide-tab:last-child {
      border-right: none
    }

    .slide-tab.active {
      background: var(--surface);
      color: var(--brand);
      box-shadow: inset 0 -2px 0 var(--brand)
    }

    .slide-tab:hover:not(.active) {
      color: var(--text);
      background: rgba(99, 102, 241, .04)
    }

    .slide-tab .stab-icon {
      display: block;
      font-size: 18px;
      margin-bottom: 4px
    }

    .slide-tab .stab-label {
      display: block;
      font-size: 11px
    }

    /* Slideshow container */
    .slideshow {
      margin-top: 0;
      border-radius: 0 0 var(--r3) var(--r3);
      overflow: hidden;
      border: 1.5px solid var(--border);
      border-top: none;
      box-shadow: var(--sh-lg)
    }

    /* Browser chrome */
    .slide-chrome {
      background: #16132E;
      padding: 11px 18px;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .sc-dots {
      display: flex;
      gap: 6px
    }

    .sc-dot {
      width: 11px;
      height: 11px;
      border-radius: 50%
    }

    .sc-url {
      flex: 1;
      background: rgba(255, 255, 255, .07);
      border-radius: 6px;
      padding: 4px 12px;
      font-family: var(--fm);
      font-size: 11px;
      color: rgba(255, 255, 255, .35);
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis
    }

    .sc-badge {
      font-family: var(--fh);
      font-size: 10px;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: var(--rf);
      background: rgba(99, 102, 241, .25);
      color: #A5B4FC
    }

    /* Slide panels */
    .slide-panels {
      position: relative;
      min-height: 420px;
      background: linear-gradient(160deg, #0D0B22 0%, #151230 100%);
      overflow: hidden
    }

    .slide-panels::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: linear-gradient(rgba(99, 102, 241, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(99, 102, 241, .06) 1px, transparent 1px);
      background-size: 40px 40px
    }

    .slide-panel {
      position: absolute;
      inset: 0;
      padding: 40px 48px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      align-items: center;
      opacity: 0;
      transform: translateX(20px);
      transition: opacity .4s ease, transform .4s ease;
      pointer-events: none
    }

    .slide-panel.active {
      opacity: 1;
      transform: none;
      pointer-events: all
    }

    .slide-panel.out {
      opacity: 0;
      transform: translateX(-20px)
    }

    /* Slide left text */
    .sp-tag {
      font-size: 9.5px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: rgba(165, 180, 252, .7);
      margin-bottom: 10px;
      font-family: var(--fh)
    }

    .sp-h {
      font-family: var(--fh);
      font-size: 26px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 10px;
      letter-spacing: -.02em;
      line-height: 1.2
    }

    .sp-p {
      font-size: 13.5px;
      color: rgba(255, 255, 255, .45);
      line-height: 1.68;
      margin-bottom: 18px;
      font-weight: 400
    }

    .sp-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 7px
    }

    .spc {
      padding: 5px 12px;
      border-radius: var(--rf);
      font-size: 11px;
      font-weight: 700;
      font-family: var(--fh)
    }

    .spc-v {
      background: rgba(99, 102, 241, .22);
      color: #A5B4FC
    }

    .spc-g {
      background: rgba(16, 185, 129, .18);
      color: #6EE7B7
    }

    .spc-o {
      background: rgba(245, 158, 11, .18);
      color: #FDE68A
    }

    .spc-r {
      background: rgba(239, 68, 68, .18);
      color: #FCA5A5
    }

    /* Slide right UI */
    .sp-ui {
      background: rgba(255, 255, 255, .04);
      border: 1px solid rgba(255, 255, 255, .08);
      border-radius: 14px;
      padding: 18px
    }

    .ui-stat-row {
      display: flex;
      gap: 8px;
      margin-bottom: 10px
    }

    .ui-stat {
      flex: 1;
      background: rgba(255, 255, 255, .06);
      border-radius: 9px;
      padding: 11px;
      text-align: center
    }

    .ui-stat-v {
      font-family: var(--fh);
      font-size: 24px;
      font-weight: 800;
      color: #fff;
      line-height: 1
    }

    .ui-stat-l {
      font-size: 8px;
      color: rgba(255, 255, 255, .35);
      margin-top: 3px;
      text-transform: uppercase;
      letter-spacing: .6px;
      font-family: var(--fh)
    }

    .ui-tbl {
      width: 100%;
      border-collapse: collapse
    }

    .ui-tbl th {
      font-size: 8px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: rgba(255, 255, 255, .25);
      padding: 4px 8px;
      text-align: left;
      font-family: var(--fh)
    }

    .ui-tbl td {
      font-size: 10.5px;
      color: rgba(255, 255, 255, .65);
      padding: 6px 8px;
      border-top: 1px solid rgba(255, 255, 255, .05)
    }

    .ui-chip {
      font-size: 8px;
      padding: 2px 6px;
      border-radius: var(--rf);
      font-weight: 700;
      font-family: var(--fh)
    }

    .uc-v {
      background: rgba(99, 102, 241, .2);
      color: #A5B4FC
    }

    .uc-g {
      background: rgba(16, 185, 129, .18);
      color: #6EE7B7
    }

    .uc-o {
      background: rgba(245, 158, 11, .18);
      color: #FDE68A
    }

    .uc-b {
      background: rgba(59, 130, 246, .18);
      color: #93C5FD
    }

    .ui-prog-row {
      display: flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 8px
    }

    .ui-prog-lbl {
      font-size: 10px;
      color: rgba(255, 255, 255, .4);
      width: 88px;
      flex-shrink: 0;
      font-family: var(--fh)
    }

    .ui-prog-bar {
      flex: 1;
      height: 5px;
      background: rgba(255, 255, 255, .07);
      border-radius: var(--rf);
      overflow: hidden
    }

    .ui-prog-fill {
      height: 100%;
      border-radius: var(--rf);
      background: linear-gradient(90deg, var(--brand), var(--brand-l));
      transition: width 1.4s cubic-bezier(.4, 0, .2, 1)
    }

    .ui-prog-pct {
      font-size: 9.5px;
      color: rgba(255, 255, 255, .35);
      width: 28px;
      text-align: right;
      flex-shrink: 0;
      font-family: var(--fm)
    }

    .ui-kanban {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 6px
    }

    .ui-kcol {
      background: rgba(255, 255, 255, .04);
      border-radius: 8px;
      padding: 7px
    }

    .ui-kcol-h {
      font-size: 7.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .9px;
      color: rgba(255, 255, 255, .3);
      margin-bottom: 5px;
      font-family: var(--fh)
    }

    .ui-card {
      background: rgba(255, 255, 255, .06);
      border-radius: 6px;
      padding: 7px;
      margin-bottom: 4px
    }

    .ui-card-t {
      font-size: 8.5px;
      color: rgba(255, 255, 255, .75);
      margin-bottom: 3px;
      font-weight: 500
    }

    .ui-task-row {
      background: rgba(255, 255, 255, .05);
      border-radius: 8px;
      padding: 9px 10px;
      margin-bottom: 6px;
      border-left: 3px solid transparent
    }

    .utr-v {
      border-left-color: var(--brand)
    }

    .utr-o {
      border-left-color: var(--amber)
    }

    .utr-g {
      border-left-color: var(--green)
    }

    .utr-t {
      font-size: 10.5px;
      color: rgba(255, 255, 255, .8);
      font-weight: 600;
      margin-bottom: 4px;
      font-family: var(--fh)
    }

    .utr-b {
      display: flex;
      gap: 6px
    }

    /* Slide navigation arrows */
    .slide-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      background: #110F28;
      border-top: 1px solid rgba(255, 255, 255, .06)
    }

    .snav-btn {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: 1.5px solid rgba(255, 255, 255, .12);
      background: rgba(255, 255, 255, .06);
      color: rgba(255, 255, 255, .6);
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all .2s;
      font-family: var(--fh)
    }

    .snav-btn:hover {
      border-color: var(--brand);
      color: var(--brand);
      background: rgba(99, 102, 241, .12)
    }

    .snav-btn:disabled {
      opacity: .25;
      cursor: not-allowed
    }

    .snav-dots {
      display: flex;
      gap: 8px;
      align-items: center
    }

    .snav-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .15);
      border: none;
      cursor: pointer;
      transition: all .22s
    }

    .snav-dot.on {
      background: var(--brand);
      transform: scale(1.35)
    }

    .snav-info {
      font-family: var(--fm);
      font-size: 11px;
      color: rgba(255, 255, 255, .28)
    }

    .snav-play {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: var(--brand);
      border: none;
      color: #fff;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all .2s;
      box-shadow: 0 4px 14px rgba(99, 102, 241, .4)
    }

    .snav-play:hover {
      background: var(--brand-l);
      transform: scale(1.1)
    }

    /* FEATURES */
    .feat-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
      margin-top: 54px
    }

    .fcard {
      background: var(--surface);
      border-radius: 20px;
      padding: 28px;
      border: 1px solid var(--border);
      transition: all .28s;
      position: relative;
      overflow: hidden
    }

    .fcard::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(99, 102, 241, .05), transparent);
      opacity: 0;
      transition: opacity .28s
    }

    .fcard:hover {
      transform: translateY(-6px);
      box-shadow: var(--sh-md);
      border-color: rgba(99, 102, 241, .3)
    }

    .fcard:hover::before {
      opacity: 1
    }

    .fcard-ico {
      width: 46px;
      height: 46px;
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 16px
    }

    .fi-v {
      background: rgba(99, 102, 241, .1)
    }

    .fi-g {
      background: rgba(16, 185, 129, .1)
    }

    .fi-o {
      background: rgba(245, 158, 11, .1)
    }

    .fi-r {
      background: rgba(239, 68, 68, .1)
    }

    .fi-b {
      background: rgba(59, 130, 246, .1)
    }

    .fi-t {
      background: rgba(20, 184, 166, .1)
    }

    .fcard-name {
      font-family: var(--fh);
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 8px;
      letter-spacing: -.01em
    }

    .fcard-desc {
      font-size: 13.5px;
      color: var(--text2);
      line-height: 1.62
    }

    /* HOW */
    .how-steps {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 28px;
      margin-top: 54px;
      position: relative
    }

    .how-steps::before {
      content: '';
      position: absolute;
      top: 30px;
      left: 12%;
      right: 12%;
      height: 2px;
      background: linear-gradient(90deg, var(--brand), var(--brand-l));
      z-index: 0
    }

    .hstep {
      text-align: center;
      position: relative;
      z-index: 1
    }

    .hstep-n {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--brand), var(--brand-d));
      color: #fff;
      font-family: var(--fh);
      font-size: 22px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      box-shadow: var(--sh-brand);
      transition: transform .25s
    }

    .hstep:hover .hstep-n {
      transform: scale(1.12) rotate(6deg)
    }

    .hstep-name {
      font-family: var(--fh);
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 7px
    }

    .hstep-desc {
      font-size: 13px;
      color: var(--text2);
      line-height: 1.55
    }

    /* ROLES */
    .roles-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-top: 54px
    }

    .rcard {
      border-radius: 20px;
      padding: 34px 28px;
      transition: transform .25s
    }

    .rcard:hover {
      transform: translateY(-7px)
    }

    .rc-admin {
      background: linear-gradient(145deg, rgba(99, 102, 241, .08), rgba(99, 102, 241, .03));
      border: 1.5px solid rgba(99, 102, 241, .2)
    }

    .rc-manager {
      background: linear-gradient(145deg, rgba(239, 68, 68, .08), rgba(239, 68, 68, .03));
      border: 1.5px solid rgba(239, 68, 68, .2)
    }

    .rc-dev {
      background: linear-gradient(145deg, rgba(245, 158, 11, .08), rgba(245, 158, 11, .03));
      border: 1.5px solid rgba(245, 158, 11, .2)
    }

    .rcard-icon {
      font-size: 36px;
      margin-bottom: 16px
    }

    .rcard-name {
      font-family: var(--fh);
      font-size: 20px;
      font-weight: 800;
      margin-bottom: 8px;
      letter-spacing: -.01em
    }

    .rcard-desc {
      font-size: 13.5px;
      color: var(--text2);
      margin-bottom: 16px;
      line-height: 1.62
    }

    .rcard-perms {
      list-style: none
    }

    .rcard-perms li {
      font-size: 13px;
      color: var(--text2);
      padding: 5px 0;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 8px
    }

    .rcard-perms li::before {
      content: '→';
      color: var(--brand);
      font-weight: 700;
      flex-shrink: 0
    }

    .rcard-perms li:last-child {
      border-bottom: none
    }

    /* CTA */
    .cta-section {
      padding: 100px 56px;
      text-align: center;
      background: linear-gradient(145deg, #2D2B7A, #4338CA, #3730A3);
      position: relative;
      overflow: hidden
    }

    /* NEW PREMIUM PRICING */
    .price-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-top: 54px
    }

    .pcard {
      background: var(--surface);
      border-radius: 24px;
      padding: 40px 32px;
      border: 1px solid var(--border);
      transition: all .3s;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column
    }

    .pcard.popular {
      border-color: var(--brand);
      box-shadow: var(--sh-brand)
    }

    .pcard.popular::before {
      content: 'Popular';
      position: absolute;
      top: 12px;
      right: -30px;
      background: var(--brand);
      color: #fff;
      font-size: 10px;
      font-weight: 800;
      padding: 4px 35px;
      transform: rotate(45deg);
      text-transform: uppercase;
      letter-spacing: 1px
    }

    .pcard-name {
      font-size: 14px;
      font-weight: 700;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 12px
    }

    .pcard-price {
      font-size: 48px;
      font-weight: 800;
      margin-bottom: 8px;
      display: flex;
      align-items: baseline;
      gap: 4px
    }

    .pcard-price span {
      font-size: 16px;
      color: var(--text3);
      font-weight: 500
    }

    .pcard-desc {
      font-size: 14px;
      color: var(--text2);
      margin-bottom: 32px;
      height: 44px
    }

    .pcard-feats {
      list-style: none;
      margin-bottom: 32px;
      flex: 1
    }

    .pcard-feats li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--text);
      margin-bottom: 12px
    }

    .pcard-feats li::before {
      content: '✓';
      color: var(--brand);
      font-weight: 800
    }

    .btn-price {
      width: 100%;
      text-align: center;
      justify-content: center;
      padding: 14px
    }

    padding: 100px 56px;
    text-align: center;
    background: linear-gradient(145deg, #2D2B7A, #4338CA, #3730A3);
    position: relative;
    overflow: hidden
    }

    .cta-section::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
      background-size: 48px 48px
    }

    .cta-glow {
      position: absolute;
      width: 560px;
      height: 560px;
      border-radius: 50%;
      background: rgba(129, 140, 248, .18);
      filter: blur(80px);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%)
    }

    .cta-inner {
      position: relative;
      z-index: 1
    }

    .cta-h {
      font-family: var(--fh);
      font-size: clamp(28px, 5vw, 52px);
      font-weight: 800;
      color: #fff;
      margin-bottom: 14px;
      line-height: 1.12;
      letter-spacing: -.02em
    }

    .cta-p {
      font-size: 17px;
      color: rgba(255, 255, 255, .6);
      margin-bottom: 36px;
      max-width: 460px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.65
    }

    .cta-btns {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 13px;
      flex-wrap: wrap
    }

    .btn-white {
      background: #fff;
      color: var(--brand-d);
      font-family: var(--fh);
      font-size: 15px;
      font-weight: 700;
      padding: 14px 32px;
      border-radius: var(--r3);
      border: none;
      cursor: pointer;
      transition: all .2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .18)
    }

    .btn-white:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 40px rgba(0, 0, 0, .22)
    }

    footer {
      padding: 36px 56px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px
    }

    .footer-logo {
      font-family: var(--fh);
      font-size: 16px;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 8px
    }

    .footer-logo em {
      color: var(--brand);
      font-style: normal
    }

    .footer-copy {
      font-size: 12px;
      color: var(--text3)
    }

    .footer-links {
      display: flex;
      gap: 20px
    }

    .footer-links a {
      font-size: 12px;
      color: var(--text3);
      transition: color .2s
    }

    .footer-links a:hover {
      color: var(--brand)
    }

    [data-r] {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity .55s ease, transform .55s ease
    }

    [data-r].on {
      opacity: 1;
      transform: none
    }

    @media(max-width:1024px) {
      .hero-wrap {
        grid-template-columns: 1fr;
        gap: 40px;
        padding: 120px 30px 60px
      }

      .hero-right {
        display: none
      }

      .slide-panel {
        grid-template-columns: 1fr
      }

      .feat-grid {
        grid-template-columns: 1fr 1fr
      }
    }

    @media(max-width:768px) {
      nav {
        padding: 14px 20px
      }

      .nav-mid {
        display: none
      }

      .section,
      .demo-section {
        padding: 60px 20px
      }

      .how-steps::before {
        display: none
      }

      .how-steps,
      .roles-grid {
        grid-template-columns: 1fr
      }

      .how-steps {
        grid-template-columns: 1fr 1fr
      }

      .feat-grid {
        grid-template-columns: 1fr
      }

      .slide-tabs {
        flex-wrap: wrap
      }

      .slide-tab {
        flex: 0 0 33.33%
      }

      .stats-inner {
        gap: 28px
      }

      .sdiv {
        display: none
      }

      .slide-panel {
        padding: 22px 20px
      }
    }
  </style>
</head>

<body>
  <div id="sd-curtain"></div>

  <!-- NAV -->
  <nav id="nav">
    <div class="logo">
      <div class="logo-mark">🚀</div>
      Sprint<em>Desk</em>
    </div>
    <div class="nav-mid">
      <a href="#demo">Demo</a>
      <a href="#features">Features</a>
      <a href="#how">How It Works</a>
      <a href="#roles">Roles</a>
    </div>
    <div class="nav-end">
      <button class="theme-btn" id="themeBtn">🌙</button>
      <a href="<?= URL_LOGIN ?>" class="btn btn-ghost">Log in</a>
      <a href="<?= URL_LOGIN ?>" class="btn btn-solid">Get Started →</a>
    </div>
  </nav>

  <!-- HERO BG -->
  <div class="hero-bg-fixed">
    <div class="hblob hb1"></div>
    <div class="hblob hb2"></div>
    <div class="hblob hb3"></div>
    <div class="hero-grid-bg"></div>
  </div>

  <!-- HERO -->
  <div class="hero-wrap">
    <div>
      <div class="hero-eyebrow"><span class="eyebrow-dot"></span>Agile Sprint Management</div>
      <h1 class="hero-h1">Plan sprints.<br>Crush tasks.<br><span class="grad">Ship faster.</span></h1>
      <p class="hero-p">SprintDesk gives your team kanban boards, sprint planning, GitHub integration and role-based
        dashboards — all in one clean platform built on PHP &amp; MySQL.</p>
      <div class="hero-btns">
        <a href="<?= URL_LOGIN ?>" class="btn btn-solid btn-lg">🚀 Open Dashboard</a>
        <a href="#demo" class="btn btn-ghost btn-lg">🎬 View Demo</a>
      </div>
      <p class="hero-note">Admin · Manager · Developer &nbsp;|&nbsp; OAuth ready &nbsp;|&nbsp; Dark mode built-in</p>
    </div>
    <div class="hero-right">
      <div style="position:relative">
        <div class="mock-shell">
          <div class="mock-bar">
            <div class="mdot" style="background:#EF4444"></div>
            <div class="mdot" style="background:#F59E0B"></div>
            <div class="mdot" style="background:#10B981"></div>
            <div class="mock-url-b">localhost/sprintdesk/backend/developer/pages/kanban.php</div>
          </div>
          <div class="mock-body">
            <div class="mock-sec-hd">🚀 Sprint 2 — E-Commerce<span class="mock-sprint-pill">Active</span></div>
            <div class="mock-cols">
              <div class="mock-col-b">
                <div class="mock-col-hd"><span>Todo</span><span>2</span></div>
                <div class="mc">
                  <div class="mc-t">Cart API</div>
                  <div class="mc-r"><span class="mchip mc-todo">Todo</span>
                    <div class="mava"></div>
                  </div>
                </div>
                <div class="mc">
                  <div class="mc-t">Payments</div>
                  <div class="mc-r"><span class="mchip mc-todo"
                      style="background:rgba(239,68,68,.14);color:#B91C1C">Critical</span>
                    <div class="mava" style="background:linear-gradient(135deg,#EF4444,#F59E0B)"></div>
                  </div>
                </div>
              </div>
              <div class="mock-col-b">
                <div class="mock-col-hd"><span>Active</span><span>2</span></div>
                <div class="mc" style="border-color:rgba(99,102,241,.3)">
                  <div class="mc-t">Product list</div>
                  <div class="mc-r"><span class="mchip mc-ip">Active</span>
                    <div class="mava" style="background:linear-gradient(135deg,var(--brand),var(--blue))"></div>
                  </div>
                </div>
                <div class="mc">
                  <div class="mc-t">Login fix</div>
                  <div class="mc-r"><span class="mchip mc-ip"
                      style="background:rgba(99,102,241,.14);color:#4338CA">Review</span>
                    <div class="mava" style="background:linear-gradient(135deg,#818CF8,#EF4444)"></div>
                  </div>
                </div>
              </div>
              <div class="mock-col-b">
                <div class="mock-col-hd"><span>Done ✓</span><span>2</span></div>
                <div class="mc">
                  <div class="mc-t">User profile</div>
                  <div class="mc-r"><span class="mchip mc-done">Done</span>
                    <div class="mava"></div>
                  </div>
                </div>
                <div class="mc">
                  <div class="mc-t">Auth system</div>
                  <div class="mc-r"><span class="mchip mc-done">Done</span>
                    <div class="mava" style="background:linear-gradient(135deg,#F59E0B,var(--green))"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mock-float mf1">✅ 5 tasks done today</div>
        <div class="mock-float mf2">🔥 Sprint 72% complete</div>
      </div>
    </div>
  </div>

  <!-- TRUSTED BY -->
  <div class="stats-row" style="background:transparent;border:none;padding-bottom:10px">
    <div
      style="text-align:center;font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:2px;margin-bottom:30px;font-family:var(--fh)">
      Trusted by innovative teams at</div>
    <div class="stats-inner" style="opacity:0.6;filter:grayscale(1)">
      <div style="font-family:var(--fh);font-weight:800;font-size:22px;color:var(--text3)">GOOGLE</div>
      <div style="font-family:var(--fh);font-weight:800;font-size:22px;color:var(--text3)">META</div>
      <div style="font-family:var(--fh);font-weight:800;font-size:22px;color:var(--text3)">MICROSOFT</div>
      <div style="font-family:var(--fh);font-weight:800;font-size:22px;color:var(--text3)">AMAZON</div>
      <div style="font-family:var(--fh);font-weight:800;font-size:22px;color:var(--text3)">NETFLIX</div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stats-inner">
      <div style="text-align:center">
        <div class="snum" data-count="3" data-sfx="+">0</div>
        <div class="slbl">User Roles</div>
      </div>
      <div class="sdiv"></div>
      <div style="text-align:center">
        <div class="snum" data-count="6" data-sfx="+">0</div>
        <div class="slbl">Core Modules</div>
      </div>
      <div class="sdiv"></div>
      <div style="text-align:center">
        <div class="snum" data-count="8" data-sfx="">0</div>
        <div class="slbl">DB Tables</div>
      </div>
      <div class="sdiv"></div>
      <div style="text-align:center">
        <div class="snum" data-count="4" data-sfx="">0</div>
        <div class="slbl">Task Statuses</div>
      </div>
      <div class="sdiv"></div>
      <div style="text-align:center">
        <div class="snum" data-count="100" data-sfx="%">0</div>
        <div class="slbl">PHP + MySQL</div>
      </div>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- SLIDESHOW DEMO SECTION                                        -->
  <!-- ============================================================ -->
  <div class="demo-section" id="demo">
    <div class="demo-inner">
      <div style="text-align:center" data-r>
        <div class="sec-tag">Interactive Slideshow</div>
        <h2 class="sec-h">See SprintDesk in action</h2>
        <p class="sec-p" style="margin:0 auto">Click any tab to jump to that feature, or use the arrows to walk through
          the full tour step by step.</p>
      </div>

      <!-- TAB SELECTOR -->
      <div class="slide-tabs" id="slideTabs" style="margin-top:28px">
        <button class="slide-tab active" data-idx="0"><span class="stab-icon">🏠</span><span
            class="stab-label">Overview</span></button>
        <button class="slide-tab" data-idx="1"><span class="stab-icon">👑</span><span
            class="stab-label">Admin</span></button>
        <button class="slide-tab" data-idx="2"><span class="stab-icon">📊</span><span
            class="stab-label">Analytics</span></button>
        <button class="slide-tab" data-idx="3"><span class="stab-icon">🔥</span><span
            class="stab-label">Sprints</span></button>
        <button class="slide-tab" data-idx="4"><span class="stab-icon">🗂️</span><span
            class="stab-label">Kanban</span></button>
        <button class="slide-tab" data-idx="5"><span class="stab-icon">💻</span><span
            class="stab-label">Developer</span></button>
      </div>

      <!-- SLIDESHOW -->
      <div class="slideshow" data-r data-r-delay=".06s">
        <!-- Chrome bar -->
        <div class="slide-chrome">
          <div class="sc-dots">
            <div class="sc-dot" style="background:#EF4444"></div>
            <div class="sc-dot" style="background:#F59E0B"></div>
            <div class="sc-dot" style="background:#10B981"></div>
          </div>
          <div class="sc-url" id="slideUrl">localhost/sprintdesk/frontend/landing/index.php</div>
          <div class="sc-badge" id="slideBadge">1 of 6</div>
        </div>

        <!-- PANELS -->
        <div class="slide-panels" id="slidePanels">

          <!-- 0: OVERVIEW -->
          <div class="slide-panel active" data-url="localhost/sprintdesk/frontend/landing/index.php">
            <div>
              <div class="sp-tag">Welcome to SprintDesk</div>
              <div class="sp-h">What is SprintDesk?</div>
              <div class="sp-p">A full-stack PHP + MySQL agile project management platform. Manage sprints, tasks, teams
                and repos — all from one place.</div>
              <div class="sp-chips">
                <span class="spc spc-v">✓ Role-Based Access</span>
                <span class="spc spc-g">✓ Sprint Boards</span>
                <span class="spc spc-o">✓ Kanban + AJAX</span>
                <span class="spc spc-r">✓ GitHub Linking</span>
                <span class="spc spc-v">✓ Analytics Charts</span>
                <span class="spc spc-g">✓ OAuth Login</span>
              </div>
            </div>
            <div class="sp-ui">
              <div class="ui-stat-row">
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#A5B4FC">3</div>
                  <div class="ui-stat-l">Roles</div>
                </div>
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#6EE7B7">4</div>
                  <div class="ui-stat-l">Statuses</div>
                </div>
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#FDE68A">8</div>
                  <div class="ui-stat-l">DB Tables</div>
                </div>
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#FCA5A5">∞</div>
                  <div class="ui-stat-l">Projects</div>
                </div>
              </div>
              <div style="background:rgba(255,255,255,.04);border-radius:9px;padding:11px;margin-top:2px">
                <div
                  style="font-size:8.5px;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px;font-family:var(--fh)">
                  Tech Stack</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <span class="spc spc-v">PHP 8.2</span><span class="spc spc-g">MySQL</span><span
                    class="spc spc-o">Chart.js</span><span class="spc spc-v">OAuth 2.0</span><span
                    class="spc spc-g">AJAX</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 1: ADMIN -->
          <div class="slide-panel" data-url="localhost/sprintdesk/backend/admin/pages/dashboard.php">
            <div>
              <div class="sp-tag">Admin Role</div>
              <div class="sp-h">Full System Control</div>
              <div class="sp-p">Admins manage all users, projects and roles. Full visibility across every team, sprint
                and task across the entire platform.</div>
              <div class="sp-chips">
                <span class="spc spc-v">Manage Users</span>
                <span class="spc spc-g">Create Projects</span>
                <span class="spc spc-o">View All Activity</span>
                <span class="spc spc-r">System Settings</span>
              </div>
            </div>
            <div class="sp-ui">
              <div class="ui-stat-row" style="margin-bottom:12px">
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#6EE7B7">4</div>
                  <div class="ui-stat-l">Users</div>
                </div>
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#FCA5A5">3</div>
                  <div class="ui-stat-l">Projects</div>
                </div>
                <div class="ui-stat">
                  <div class="ui-stat-v" style="color:#FDE68A">8</div>
                  <div class="ui-stat-l">Tasks</div>
                </div>
              </div>
              <table class="ui-tbl">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Alex Admin</td>
                    <td><span class="ui-chip uc-v">Admin</span></td>
                    <td><span class="ui-chip uc-g">Active</span></td>
                  </tr>
                  <tr>
                    <td>Maria Manager</td>
                    <td><span class="ui-chip uc-o">Manager</span></td>
                    <td><span class="ui-chip uc-g">Active</span></td>
                  </tr>
                  <tr>
                    <td>Dev One</td>
                    <td><span class="ui-chip uc-b">Developer</span></td>
                    <td><span class="ui-chip uc-g">Active</span></td>
                  </tr>
                  <tr>
                    <td>Dev Two</td>
                    <td><span class="ui-chip uc-b">Developer</span></td>
                    <td><span class="ui-chip uc-g">Active</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 2: ANALYTICS -->
          <div class="slide-panel" data-url="localhost/sprintdesk/backend/admin/pages/analytics.php">
            <div>
              <div class="sp-tag">Analytics Module</div>
              <div class="sp-h">Real-Time Insights</div>
              <div class="sp-p">Track sprint velocity, task completion rates and team workload. Chart.js powered charts
                pulling live data from MySQL.</div>
              <div class="sp-chips">
                <span class="spc spc-g">Burndown Charts</span>
                <span class="spc spc-o">Velocity Tracking</span>
                <span class="spc spc-v">PDF Export</span>
              </div>
            </div>
            <div class="sp-ui">
              <div
                style="font-size:8.5px;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:10px;font-family:var(--fh)">
                Sprint Progress</div>
              <div class="ui-prog-row">
                <div class="ui-prog-lbl">E-Commerce</div>
                <div class="ui-prog-bar">
                  <div class="ui-prog-fill" style="width:72%"></div>
                </div>
                <div class="ui-prog-pct">72%</div>
              </div>
              <div class="ui-prog-row">
                <div class="ui-prog-lbl">Analytics DB</div>
                <div class="ui-prog-bar">
                  <div class="ui-prog-fill" style="width:45%"></div>
                </div>
                <div class="ui-prog-pct">45%</div>
              </div>
              <div class="ui-prog-row">
                <div class="ui-prog-lbl">Mobile API</div>
                <div class="ui-prog-bar">
                  <div class="ui-prog-fill" style="width:20%"></div>
                </div>
                <div class="ui-prog-pct">20%</div>
              </div>
              <div style="display:flex;gap:7px;margin-top:11px;flex-wrap:wrap">
                <span class="spc spc-g">✓ 5 done today</span>
                <span class="spc spc-o">⚡ 3 in review</span>
                <span class="spc spc-r">🐛 1 open bug</span>
              </div>
            </div>
          </div>

          <!-- 3: SPRINT PLANNING -->
          <div class="slide-panel" data-url="localhost/sprintdesk/backend/manager/pages/sprints.php">
            <div>
              <div class="sp-tag">Manager Role</div>
              <div class="sp-h">Sprint Planning</div>
              <div class="sp-p">Managers create sprints with goals and deadlines, assign tasks to developers, and track
                progress in real time from their dashboard.</div>
              <div class="sp-chips">
                <span class="spc spc-v">Create Sprints</span>
                <span class="spc spc-g">Assign Tasks</span>
                <span class="spc spc-o">Track Velocity</span>
              </div>
            </div>
            <div class="sp-ui">
              <div
                style="font-size:8.5px;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:10px;font-family:var(--fh)">
                Active Sprints</div>
              <div
                style="background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.22);border-radius:9px;padding:10px 12px;margin-bottom:8px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
                  <span style="font-size:11px;color:#fff;font-weight:700;font-family:var(--fh)">Sprint 2 —
                    E-Commerce</span>
                  <span class="spc spc-g">Active</span>
                </div>
                <div class="ui-prog-bar" style="height:5px">
                  <div class="ui-prog-fill" style="width:72%"></div>
                </div>
                <div style="font-size:9px;color:rgba(255,255,255,.3);margin-top:5px;font-family:var(--fm)">72% · 4 days
                  left · 5/8 done</div>
              </div>
              <div
                style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.18);border-radius:9px;padding:10px 12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
                  <span style="font-size:11px;color:#fff;font-weight:700;font-family:var(--fh)">Sprint 1 —
                    Analytics</span>
                  <span class="spc spc-o">Active</span>
                </div>
                <div class="ui-prog-bar" style="height:5px">
                  <div class="ui-prog-fill" style="width:45%"></div>
                </div>
                <div style="font-size:9px;color:rgba(255,255,255,.3);margin-top:5px;font-family:var(--fm)">45% · 8 days
                  left · 3/7 done</div>
              </div>
            </div>
          </div>

          <!-- 4: KANBAN -->
          <div class="slide-panel" data-url="localhost/sprintdesk/backend/developer/pages/kanban.php">
            <div>
              <div class="sp-tag">Kanban Board</div>
              <div class="sp-h">Drag &amp; Drop Tasks</div>
              <div class="sp-p">Move tasks between columns with drag and drop. Status updates saved instantly to MySQL
                via AJAX — no page reload needed at all.</div>
              <div class="sp-chips">
                <span class="spc spc-v">Drag &amp; Drop</span>
                <span class="spc spc-g">AJAX Save</span>
                <span class="spc spc-o">4 Columns</span>
              </div>
            </div>
            <div class="sp-ui" style="padding:12px">
              <div class="ui-kanban">
                <div class="ui-kcol" style="border-top:2px solid rgba(148,150,204,.4)">
                  <div class="ui-kcol-h">Todo</div>
                  <div class="ui-card">
                    <div class="ui-card-t">Cart API</div><span class="ui-chip uc-o">High</span>
                  </div>
                  <div class="ui-card">
                    <div class="ui-card-t">Payments</div><span class="ui-chip"
                      style="background:rgba(239,68,68,.18);color:#FCA5A5;font-size:8px;padding:2px 6px;border-radius:9999px;font-weight:700;font-family:var(--fh)">Critical</span>
                  </div>
                </div>
                <div class="ui-kcol" style="border-top:2px solid var(--amber)">
                  <div class="ui-kcol-h">Active</div>
                  <div class="ui-card" style="border:1px solid rgba(99,102,241,.3)">
                    <div class="ui-card-t">Listing page</div><span class="ui-chip uc-v">Active</span>
                  </div>
                </div>
                <div class="ui-kcol" style="border-top:2px solid var(--brand)">
                  <div class="ui-kcol-h">Review</div>
                  <div class="ui-card">
                    <div class="ui-card-t">Bug fix</div><span class="ui-chip uc-v">Review</span>
                  </div>
                </div>
                <div class="ui-kcol" style="border-top:2px solid var(--green)">
                  <div class="ui-kcol-h">Done ✓</div>
                  <div class="ui-card">
                    <div class="ui-card-t">Profile</div><span class="ui-chip uc-g">Done</span>
                  </div>
                  <div class="ui-card">
                    <div class="ui-card-t">Auth</div><span class="ui-chip uc-g">Done</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 5: DEVELOPER -->
          <div class="slide-panel" data-url="localhost/sprintdesk/backend/developer/pages/dashboard.php">
            <div>
              <div class="sp-tag">Developer Role</div>
              <div class="sp-h">Developer View</div>
              <div class="sp-p">Developers see only their assigned tasks and sprint progress. Link GitHub commits and
                pull requests directly to tickets on the kanban board.</div>
              <div class="sp-chips">
                <span class="spc spc-v">My Tasks Only</span>
                <span class="spc spc-g">GitHub Linking</span>
                <span class="spc spc-o">Sprint Progress</span>
              </div>
            </div>
            <div class="sp-ui">
              <div
                style="font-size:8.5px;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:10px;font-family:var(--fh)">
                My Sprint Tasks</div>
              <div class="ui-task-row utr-o">
                <div class="utr-t">Product listing page</div>
                <div class="utr-b"><span class="spc spc-o">In Progress</span><span class="spc spc-v">5 pts</span></div>
              </div>
              <div class="ui-task-row utr-v">
                <div class="utr-t">Fix login redirect bug</div>
                <div class="utr-b"><span class="spc spc-v">In Review</span><span class="spc spc-o">2 pts</span></div>
              </div>
              <div class="ui-task-row utr-g">
                <div class="utr-t">User profile page</div>
                <div class="utr-b"><span class="spc spc-g">✓ Done</span><span class="spc spc-v">3 pts</span></div>
              </div>
            </div>
          </div>

        </div><!-- /panels -->

        <!-- NAVIGATION -->
        <div class="slide-nav">
          <button class="snav-btn" id="prevBtn" disabled>←</button>
          <div style="display:flex;align-items:center;gap:12px">
            <div class="snav-dots" id="snavDots">
              <button class="snav-dot on"></button>
              <button class="snav-dot"></button>
              <button class="snav-dot"></button>
              <button class="snav-dot"></button>
              <button class="snav-dot"></button>
              <button class="snav-dot"></button>
            </div>
          </div>
          <button class="snav-play" id="autoPlayBtn" title="Auto-play">▶</button>
          <div class="snav-info" id="snavInfo">Slide 1 of 6</div>
          <button class="snav-btn" id="nextBtn">→</button>
        </div>
      </div><!-- /slideshow -->
    </div>
  </div>

  <!-- FEATURES -->
  <section class="section" id="features">
    <div class="sec-inner">
      <div data-r>
        <div class="sec-tag">Features</div>
        <h2 class="sec-h">Everything your team needs</h2>
        <p class="sec-p">Built for the full dev lifecycle — from backlog to deployment.</p>
      </div>
      <div class="feat-grid">
        <div class="fcard" data-r data-r-delay=".04s">
          <div class="fcard-ico fi-v">🗂️</div>
          <div class="fcard-name">Kanban Board</div>
          <div class="fcard-desc">Drag and drop tasks across four columns with AJAX saves. Real-time status updates, no
            page reload.</div>
        </div>
        <div class="fcard" data-r data-r-delay=".08s">
          <div class="fcard-ico fi-r">🔥</div>
          <div class="fcard-name">Sprint Management</div>
          <div class="fcard-desc">Create sprints with goals, deadlines and story points. Track velocity and progress
            with live charts.</div>
        </div>
        <div class="fcard" data-r data-r-delay=".12s">
          <div class="fcard-ico fi-o">👥</div>
          <div class="fcard-name">Role-Based Access</div>
          <div class="fcard-desc">Admin, Manager and Developer each get a tailored dashboard. OAuth login via Google and
            GitHub.</div>
        </div>
        <div class="fcard" data-r data-r-delay=".16s">
          <div class="fcard-ico fi-g">🔗</div>
          <div class="fcard-name">GitHub Integration</div>
          <div class="fcard-desc">Link commits and pull requests directly to tasks. Track code changes alongside your
            tickets.</div>
        </div>
        <div class="fcard" data-r data-r-delay=".20s">
          <div class="fcard-ico fi-b">📊</div>
          <div class="fcard-name">Analytics & Reports</div>
          <div class="fcard-desc">Sprint burndown and task breakdown charts powered by Chart.js with live MySQL data.
          </div>
        </div>
        <div class="fcard" data-r data-r-delay=".24s">
          <div class="fcard-ico fi-t">📜</div>
          <div class="fcard-name">Activity Log</div>
          <div class="fcard-desc">Full audit trail of every action across all projects. Real-time notifications for task
            assignments.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section" id="how" style="background:var(--surface)">
    <div class="sec-inner">
      <div style="text-align:center" data-r>
        <div class="sec-tag">Workflow</div>
        <h2 class="sec-h">From backlog to shipped in 4 steps</h2>
      </div>
      <div class="how-steps">
        <div class="hstep" data-r data-r-delay=".04s">
          <div class="hstep-n">1</div>
          <div class="hstep-name">Create Project</div>
          <div class="hstep-desc">Admin creates a project, assigns a manager and invites the team.</div>
        </div>
        <div class="hstep" data-r data-r-delay=".08s">
          <div class="hstep-n">2</div>
          <div class="hstep-name">Plan Sprint</div>
          <div class="hstep-desc">Manager creates a sprint, adds tasks with priorities and assigns developers.</div>
        </div>
        <div class="hstep" data-r data-r-delay=".12s">
          <div class="hstep-n">3</div>
          <div class="hstep-name">Track Progress</div>
          <div class="hstep-desc">Developers move tasks on the kanban and link GitHub commits to tickets.</div>
        </div>
        <div class="hstep" data-r data-r-delay=".16s">
          <div class="hstep-n">4</div>
          <div class="hstep-name">Ship & Review</div>
          <div class="hstep-desc">Complete the sprint, review analytics and plan the next iteration.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ROLES -->
  <section class="section" id="roles">
    <div class="sec-inner">
      <div data-r>
        <div class="sec-tag">Access Control</div>
        <h2 class="sec-h">Three roles, one platform</h2>
        <p class="sec-p">Each role gets its own focused workspace tailored to their responsibilities.</p>
      </div>
      <div class="roles-grid">
        <div class="rcard rc-admin" data-r data-r-delay=".04s">
          <div class="rcard-icon">👑</div>
          <div class="rcard-name">Admin</div>
          <div class="rcard-desc">Full system control — manages users, projects and platform config.</div>
          <ul class="rcard-perms">
            <li>Manage all users & roles</li>
            <li>Create & archive projects</li>
            <li>Full activity log</li>
            <li>System settings</li>
          </ul>
        </div>
        <div class="rcard rc-manager" data-r data-r-delay=".08s">
          <div class="rcard-icon">🗂️</div>
          <div class="rcard-name">Manager</div>
          <div class="rcard-desc">Oversees sprint planning, task assignment and team progress.</div>
          <ul class="rcard-perms">
            <li>Create & manage sprints</li>
            <li>Assign tasks to devs</li>
            <li>Track team velocity</li>
            <li>Generate reports</li>
          </ul>
        </div>
        <div class="rcard rc-dev" data-r data-r-delay=".12s">
          <div class="rcard-icon">💻</div>
          <div class="rcard-name">Developer</div>
          <div class="rcard-desc">Focused view of personal tasks, kanban and GitHub linking.</div>
          <ul class="rcard-perms">
            <li>Update own tasks on kanban</li>
            <li>Link GitHub commits & PRs</li>
            <li>Add comments to tasks</li>
            <li>View sprint progress</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <!-- SYSTEM ARCHITECTURE -->
  <section class="section" id="architecture" style="background:var(--surface2);overflow:hidden">
    <div class="sec-inner">
      <div style="text-align:center" data-r>
        <div class="sec-tag">Modern Stack</div>
        <h2 class="sec-h">Live System Architecture</h2>
        <p style="margin:0 auto" class="sec-p">A high-performance, real-time environment built on modern web standards.
        </p>
      </div>

      <div
        style="margin-top:60px;position:relative;padding:40px;background:var(--surface);border-radius:24px;border:1px solid var(--border);backdrop-filter:blur(20px)"
        data-r>
        <div class="arch-grid"
          style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:40px;position:relative;z-index:2">
          <!-- FRONTEND -->
          <div style="text-align:center">
            <div style="font-size:40px;margin-bottom:15px">💻</div>
            <h4 style="margin-bottom:10px">Frontend Layer</h4>
            <p style="font-size:12px;color:var(--text3)">Vanilla JS + CSS3 + HTML5<br>AJAX Real-time Updates</p>
            <div
              style="margin-top:20px;height:2px;background:var(--brand);box-shadow:0 0 10px var(--brand);width:80%;margin-left:10%">
            </div>
          </div>
          <!-- BACKEND -->
          <div style="text-align:center">
            <div style="font-size:40px;margin-bottom:15px">⚙️</div>
            <h4 style="margin-bottom:10px">PHP Backend</h4>
            <p style="font-size:12px;color:var(--text3)">Modular API Routes<br>PDO Prepared Statements</p>
            <div
              style="margin-top:20px;height:2px;background:var(--purple);box-shadow:0 0 10px var(--purple);width:80%;margin-left:10%">
            </div>
          </div>
          <!-- DATABASE -->
          <div style="text-align:center">
            <div style="font-size:40px;margin-bottom:15px">🗄️</div>
            <h4 style="margin-bottom:10px">MySQL Database</h4>
            <p style="font-size:12px;color:var(--text3)">Relational Schema<br>Optimized Indexing</p>
            <div
              style="margin-top:20px;height:2px;background:var(--blue);box-shadow:0 0 10px var(--blue);width:80%;margin-left:10%">
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <div class="cta-section">
    <div class="cta-glow"></div>
    <div class="cta-inner" data-r>
      <h2 class="cta-h">Ready to ship faster?</h2>
      <p class="cta-p">Sign in to your SprintDesk workspace and start managing projects like a pro team.</p>
      <div class="cta-btns">
        <a href="<?= URL_LOGIN ?>" class="btn-white">🚀 Open Dashboard</a>
        <a href="<?= URL_LOGIN ?>" class="btn btn-outline-white">Login with Google →</a>
      </div>
      <p style="font-size:12px;color:rgba(255,255,255,.35);margin-top:18px">Demo password for all accounts: <strong
          style="color:rgba(255,255,255,.55)">password</strong></p>
    </div>
  </div>

  <footer>
    <div class="footer-logo">
      <div class="logo-mark" style="width:26px;height:26px;font-size:13px">🚀</div>Sprint<em>Desk</em>
    </div>
    <div class="footer-copy">© 2025 SprintDesk · Agile Project Management · PHP + MySQL</div>
    <div class="footer-links">
      <a href="#features">Features</a>
      <a href="#how">How It Works</a>
      <a href="<?= URL_LOGIN ?>">Login</a>
    </div>
  </footer>

  <script>
    'use strict';
    /* THEME */
    const T = { k: 'sd_theme', get() { return localStorage.getItem(this.k) || 'light' }, set(v) { localStorage.setItem(this.k, v); document.documentElement.setAttribute('data-theme', v) }, toggle() { this.set(this.get() === 'dark' ? 'light' : 'dark') } };
    document.documentElement.setAttribute('data-theme', T.get());
    const thBtn = document.getElementById('themeBtn');
    thBtn.textContent = T.get() === 'dark' ? '☀️' : '🌙';
    thBtn.addEventListener('click', () => { T.toggle(); thBtn.textContent = T.get() === 'dark' ? '☀️' : '🌙' });

    /* NAV */
    window.addEventListener('scroll', () => document.getElementById('nav').classList.toggle('stuck', scrollY > 24));

    /* CURTAIN */
    const curtain = document.getElementById('sd-curtain');
    document.body.addEventListener('click', e => {
      const a = e.target.closest('a[href]');
      if (!a) return;
      const h = a.getAttribute('href');
      if (!h || h.startsWith('#') || h.startsWith('javascript') || h.startsWith('mailto') || a.target === '_blank') return;
      e.preventDefault(); curtain.classList.add('go');
      setTimeout(() => location.href = h, 400);
    });

    /* SCROLL REVEAL */
    const ro = new IntersectionObserver(entries => entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('on'); ro.unobserve(e.target); } }), { threshold: .08 });
    document.querySelectorAll('[data-r]').forEach(el => { if (el.dataset.rDelay) el.style.transitionDelay = el.dataset.rDelay; ro.observe(el); });

    /* COUNT UP */
    document.querySelectorAll('[data-count]').forEach(el => {
      const io = new IntersectionObserver(([e]) => {
        if (!e.isIntersecting) return; io.unobserve(el);
        const t = parseInt(el.dataset.count), sfx = el.dataset.sfx || ''; let n = 0;
        const iv = setInterval(() => { n = Math.min(n + Math.max(1, Math.ceil(t / 40)), t); el.textContent = n + sfx; if (n >= t) clearInterval(iv); }, 28);
      }, { threshold: .5 }); io.observe(el);
    });

    /* ============================================================ */
    /* SLIDESHOW                                                     */
    /* ============================================================ */
    const panels = Array.from(document.querySelectorAll('.slide-panel'));
    const tabs = Array.from(document.querySelectorAll('.slide-tab'));
    const dots = Array.from(document.querySelectorAll('.snav-dot'));
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const autoBtn = document.getElementById('autoPlayBtn');
    const urlEl = document.getElementById('slideUrl');
    const badgeEl = document.getElementById('slideBadge');
    const infoEl = document.getElementById('snavInfo');
    let cur = 0, autoTimer = null, isAuto = false;

    function goTo(idx) {
      panels[cur].classList.remove('active'); panels[cur].classList.add('out');
      setTimeout(() => panels[cur]?.classList.remove('out'), 420);
      tabs[cur].classList.remove('active'); dots[cur].classList.remove('on');
      cur = idx;
      panels[cur].classList.add('active'); tabs[cur].classList.add('active'); dots[cur].classList.add('on');
      urlEl.textContent = panels[cur].dataset.url || '';
      badgeEl.textContent = `${cur + 1} of ${panels.length}`;
      infoEl.textContent = `Slide ${cur + 1} of ${panels.length}`;
      prevBtn.disabled = cur === 0;
      nextBtn.disabled = cur === panels.length - 1;
      // re-animate progress bars
      panels[cur].querySelectorAll('.ui-prog-fill').forEach(b => { const w = b.style.width; b.style.width = '0'; setTimeout(() => b.style.width = w, 80); });
    }

    prevBtn.addEventListener('click', () => { if (cur > 0) { goTo(cur - 1); } });
    nextBtn.addEventListener('click', () => { if (cur < panels.length - 1) goTo(cur + 1); });
    tabs.forEach((t, i) => t.addEventListener('click', () => goTo(i)));
    dots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));

    autoBtn.addEventListener('click', () => {
      isAuto = !isAuto;
      autoBtn.textContent = isAuto ? '⏸' : '▶';
      autoBtn.style.background = isAuto ? 'var(--red)' : 'var(--brand)';
      if (isAuto) {
        autoTimer = setInterval(() => {
          const next = (cur + 1) % panels.length;
          goTo(next);
        }, 3500);
      } else {
        clearInterval(autoTimer);
      }
    });

    // keyboard nav
    document.addEventListener('keydown', e => {
      if (e.key === 'ArrowRight' && cur < panels.length - 1) goTo(cur + 1);
      if (e.key === 'ArrowLeft' && cur > 0) goTo(cur - 1);
    });
  </script>
</body>

</html>