# PHP Donations Form

A donations form to run on your own site. It works great on mobile handsets as well because it is powered by the [Bootstrap](http://getbootstrap.com) responsive framework. Requires an active Stripe account for use.

## Who Should Use It

This project is ideal for non-profits and political campaigns that need on-site donations. Note that it any page that processes finanancial data should be served over SSL (`https://`).

## Quick Start

1. Copy `config/app-config.php-dist` to `app-config.php` and complete with your Stripe API keys
2. Update the Google Analytics code in `index.php` to properly track for your site (or remove it entirely)
3. Replace `assets/img/logo.png` with something more meaningful
4. Test everything! (no money is processed in test mode)
5. Switch your Stripe API keys to the production set
6. Switch your Stripe account to Live mode
7. Test everything! (small dollar amounts, as these are actually processed)

## Customizing

You can override the default styles in `assets/css/donations-form.css`. You can do most changes to the layout of the objects without needing to update the JavaScript as long as the form field IDs do not change.

## How It Works

The form on the landing page interacts with the Stripe JavaScript API to obtain a token for the provided credit card. The token is then used on the backend (along with additional form fields) to create a payment in Stripe.

## Questions or issues?

Simply [file an issue]() on our GitHub project.