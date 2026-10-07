<?php
declare(strict_types=1);
// Fixed Midnight backdrop shared by the admin login and every admin page:
// the brand swoosh (same curve as the home hero) plus soft indigo/gold glows
// for the frosted-glass panels to blur.
?>
<div class="admin-backdrop" aria-hidden="true">
    <span class="admin-glow admin-glow-indigo"></span>
    <span class="admin-glow admin-glow-gold"></span>
    <svg class="admin-swoosh" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice">
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.55"/>
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.65"/>
    </svg>
    <svg class="admin-swoosh-mobile" viewBox="0 0 100 100" preserveAspectRatio="none">
        <path d="M108 24 C 70 20, -4 30, 8 46 S 104 60, 92 74 S 30 84, -12 90" fill="none" stroke="#34348A" stroke-width="110" stroke-linecap="round" opacity="0.55" vector-effect="non-scaling-stroke"/>
        <path d="M108 24 C 70 20, -4 30, 8 46 S 104 60, 92 74 S 30 84, -12 90" fill="none" stroke="#F7CB1E" stroke-width="2" stroke-linecap="round" opacity="0.65" vector-effect="non-scaling-stroke"/>
    </svg>
</div>
