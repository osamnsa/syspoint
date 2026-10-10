<?php
declare(strict_types=1);

// /robots.txt — let search engines in, keep them out of the admin, cart and checkout.
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n",
     "Allow: /\n",
     "Disallow: " . path('admin') . "\n",
     "Disallow: " . path('cart') . "\n",
     "Disallow: " . path('checkout') . "\n",
     "Disallow: " . path('order/') . "\n",
     "Disallow: " . path('paystack/') . "\n",
     "Disallow: " . path('shop/search') . "\n",
     "\n",
     "Sitemap: " . public_url('sitemap.xml') . "\n";
