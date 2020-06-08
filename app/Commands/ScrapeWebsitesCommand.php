<?php

namespace App\Commands;

use Goutte\Client;
use Illuminate\Support\Facades\DB;
use LaravelZero\Framework\Commands\Command;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeWebsitesCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'all';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Scrape configured websites';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (empty(env('SITE_ONE_URL')) && empty(env('SITE_TWO_URL'))) {
            return $this->error(' ERROR: One or more environment variables failed assertions: SITE_XXX_URL is missing ');
        }

        if (!empty(env('SITE_ONE_URL'))) {
            $this->crawl(
                env('SITE_ONE_URL'),
                env('SITE_ONE_NAME'),
                '.search-result:not(.unruly_ad):not(.search-result--sponsored-ad):not(.js-run-script)',
                [
                    'price' => '.is-price',
                    'title' => '.is-title',
                    'location' => '.is-location',
                    'description' => '.search-result__description',
                ]
            );
        }

        if (!empty(env('SITE_TWO_URL'))) {
            $this->crawl(
                env('SITE_TWO_URL'),
                env('SITE_TWO_NAME'),
                '.profilelisting.white-bg:not(.paid-advert)',
                [
                    'price' => '.listingprice',
                    'title' => '.headline',
                    'location' => '.location',
                    'description' => '.description',
                ]
            );
        }
    }

    public function crawl(string $website, string $name, string $anchor, array $selectors)
    {
        $client = new Client();
        $crawler = $client->request('GET', $website);

        $listings = $crawler->filter($anchor)->each(static function (Crawler $parentCrawler, $i) use ($name, $selectors) {
            return [
                'website' => $name,
                'price' => $parentCrawler->filter($selectors['price'])->text(),
                'title' => $parentCrawler->filter($selectors['title'])->text(),
                'location' => $parentCrawler->filter($selectors['location'])->text(),
                'description' => $parentCrawler->filter($selectors['description'])->text()
            ];
        });

        $listings = array_slice($listings, 0, 5, true);

        foreach ($listings as $i => $listing) {

            $existingListing = DB::table('listings')->where($listing)->latest('id')->first();

            if ($existingListing) {
                $this->question(" This listing has already been scraped. Skipping.. ");
                continue;
            }

            DB::table('listings')->insert($listing);

            $transport = (new Swift_SmtpTransport(env('MAIL_HOST'), env('MAIL_PORT'), env('MAIL_ENCRYPTION')))
                ->setUsername(env('MAIL_USERNAME'))
                ->setPassword(env('MAIL_PASSWORD'));

            $mailer = new Swift_Mailer($transport);

            $body = "Price: {$listing['price']}\n";
            $body .= "Title: {$listing['title']}\n";
            $body .= "Location: {$listing['location']}\n";
            $body .= "Description: {$listing['description']}\n";

            $message = (new Swift_Message("$name - New Listing"))
                ->setFrom([env('MAIL_USERNAME') => env('MAIL_FROM')])
                ->setTo(env('MAIL_USERNAME'))
                ->setBody($body);

            $mailer->send($message);
            $this->info(" New listing found.. Emailing.. ");
        }
    }
}
