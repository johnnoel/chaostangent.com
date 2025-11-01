<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Post;
use App\Image\Action;
use App\Image\CropGuesser;
use App\Image\FileHandler;
use App\Image\Source;

readonly final class GuessOldImageCrop implements Processor
{
    public function __construct(private FileHandler $fileHandler)
    {
    }

    #[\Override]
    public function process(Post $post): void
    {
        $extra = $post->getExtra();

        if (
            !array_key_exists('image', $extra) ||
            !is_string($extra['image']) ||
            trim($extra['image']) === ''
        ) {
            return;
        }

        $croppedImage = $this->guessOldImageCrop(urldecode($extra['image']));

        if ($croppedImage === null) {
            return;
        }

        $post->setImage($croppedImage->toArray());
        unset($extra['image']);
        $post->setExtra($extra);
    }

    #[\Override]
    public function getSlug(): string
    {
        return 'guess-old-image-crop';
    }

    private function guessOldImageCrop(string $oldImage): ?Source
    {
        $cropPath = $oldImage;
        if (str_starts_with($cropPath, '/media/')) {
            $cropPath = substr($cropPath, strlen('/media/'));
        }

        $fullCropPath = $this->fileHandler->getSourcePath(new Source($cropPath, []));
        if (!file_exists($fullCropPath)) {
            return null;
        }

        $cropSizes = getimagesize($fullCropPath);
        if ($cropSizes === false) {
            return null;
        }

        $count = 0;
        $srcPath = preg_replace('/-\d+x\d+\.jpg$/', '.jpg', $cropPath, count: $count);
        $fullSrcPath = $this->fileHandler->getSourcePath(new Source($srcPath, []));

        if ($count === 0) { // can't differentiate between the cropped image and its source
            return null;
        }

        if (!file_exists($fullSrcPath)) {
            // try a png source
            $fullSrcPath = substr($fullSrcPath, 0, -3) . 'png';

            if (!file_exists($fullSrcPath)) {
                return null;
            }
        }

        $srcSizes = getimagesize($fullSrcPath);
        if ($srcSizes === false) {
            return null;
        }

        $cropGuesser = new CropGuesser();
        try {
            $crop = $cropGuesser->guessCrop($fullSrcPath, $fullCropPath);

            $ret = new Source($srcPath, [
                new Action('crop', sprintf('%dx%d+%d+%d', $crop['w'], $crop['h'], $crop['x'], $crop['y'])),
                new Action('resize', sprintf('%dx%d', $cropSizes[0], $cropSizes[1])),
            ]);

            return $ret;
        } catch (\Exception) {
        }

        return null;
    }
}

/*
{#---
title: 'First days in Aion'
subtitle: null
alias: first-days-in-aion
summary: |-
    {{ thumbnails([{ 'src': '2009/08/aion-2009-08-13-19-30-04-01.png', 'actions': [ 'resize:lead' ], 'caption': 'Aion login screenshot' }]) }}
    <p>I have decided, perhaps foolishly, to start playing an MMO - <a href="http://eu.aiononline.com/uk/">Aion</a>. If one were to ask the reason for me deciding this, I could not give a single answer. I can quantify my reasoning, but not give a single definitive motive.</p>
    <p>To backtrack, I don't usually play MMO's: I haven't played one extensively since <a href="http://www.uoherald.com/news/">Ultima Online</a> back when 28.8 modems were considered cutting edge. I have dabbled only once since, joining the first iteration of <a href="http://www.guildwars.com/">Guild Wars</a>, an <a href="http://www.ncsoft.com/global/">NCSoft</a> cost-to-buy but free-to-play system that was bought for playing with my significant other of the time; suffice to say neither of us took to it and that experiment ended with a little fanfare. I did hear of Aion when it was released in Korea at the tail end of 2008, although at the time it blended in with the general background noise of the Korean gaming scene of which MMO's play a large part.</p>
    <blockquote class="pullquote">I capitulated and purchased the Collector's Edition that had been beckoning to me, siren-like, on Steam</blockquote>
    <p>I think one of the aspects which swayed me into at least trying Aion was the aesthetics. Despite my ordinary avoidance of superficiality, I am by nature drawn to pretty things, a severe character flaw I'm sure. So it had crested the first hurdle in getting my attention. The second draw was the pleasant buzz surrounding it, <a href="http://www.srsfkn.biz/2009/06/17/hurf-durf-stuff/">anime</a> and <a href="http://kotaku.com/5326355/aion-beta-finds-its-voice">game</a> blogs alike weren't exactly haemorrhaging praise but there was a decent undercurrent to the otherwise acerbic MMO discussion. Partnered with the looks came the lore, typical high-fantasy fare with a generous sprinkling of ethereal names that only just manages to be convincing of when it was conceived: moments before it was decided to be an MMO. And then there was the marketing:</p>
date: '2009-08-13T22:28:26+00:00'
created: '2014-09-20T14:39:06+00:00'
updated: '2025-10-12T13:37:34+00:00'
published: true
extra:
    image: '/media/2009/08/aion-2009-08-13-19-30-04-01-320x320.jpg'
image: null
categories:
    - Videogames
tags:
    - videogames
    - 'collectors edition'
    - gametrailers
    - aion
    - 'guild wars'
    - Videogames
    - 'ultima online'
    - mmorpg
    - marketing
    - ncsoft
    - 'video games'
    - mmo
    - recording
    - steam
---#}
{{ thumbnails([{ 'src': '2009/08/aion-2009-08-13-19-30-04-01.png', 'actions': [ 'resize:lead' ], 'caption': 'Aion login screenshot' }]) }}
<p>I have decided, perhaps foolishly, to start playing an MMO - <a href="http://eu.aiononline.com/uk/">Aion</a>. If one were to ask the reason for me deciding this, I could not give a single answer. I can quantify my reasoning, but not give a single definitive motive.</p>
<p>To backtrack, I don't usually play MMO's: I haven't played one extensively since <a href="http://www.uoherald.com/news/">Ultima Online</a> back when 28.8 modems were considered cutting edge. I have dabbled only once since, joining the first iteration of <a href="http://www.guildwars.com/">Guild Wars</a>, an <a href="http://www.ncsoft.com/global/">NCSoft</a> cost-to-buy but free-to-play system that was bought for playing with my significant other of the time; suffice to say neither of us took to it and that experiment ended with a little fanfare. I did hear of Aion when it was released in Korea at the tail end of 2008, although at the time it blended in with the general background noise of the Korean gaming scene of which MMO's play a large part.</p>
<blockquote class="pullquote">I capitulated and purchased the Collector's Edition that had been beckoning to me, siren-like, on Steam</blockquote>
<p>I think one of the aspects which swayed me into at least trying Aion was the aesthetics. Despite my ordinary avoidance of superficiality, I am by nature drawn to pretty things, a severe character flaw I'm sure. So it had crested the first hurdle in getting my attention. The second draw was the pleasant buzz surrounding it, <a href="http://www.srsfkn.biz/2009/06/17/hurf-durf-stuff/">anime</a> and <a href="http://kotaku.com/5326355/aion-beta-finds-its-voice">game</a> blogs alike weren't exactly haemorrhaging praise but there was a decent undercurrent to the otherwise acerbic MMO discussion. Partnered with the looks came the lore, typical high-fantasy fare with a generous sprinkling of ethereal names that only just manages to be convincing of when it was conceived: moments before it was decided to be an MMO. And then there was the marketing:</p>
{{ video([{ 'src': '2009/08/AION Podcast Episode 1 The World and Lore.mp4', 'type': 'video/mp4' }], '2009/08/AION Podcast Episode 1 The World and Lore.jpg') }}
{{ video([{ 'src': '2009/08/AION Podcast Episode 2 Classes and Customization.mp4', 'type': 'video/mp4' }], '2009/08/AION Podcast Episode 2 Classes and Customization.jpg') }}
<p>NCSoft opted for the <a href="http://www.gametrailers.com/video/montferrat-assassins-creed/26581">Assassin's Creed</a> approach to marketing by fronting the videos and likely related content with a young lady, in this case <a href="http://www.tentonhammer.com/taxonomy/term/2038">Lani Blazier</a>. It's an obvious move but the Aion "devblogs" go beyond the standard market pandering and provide an insight into the creation (or at least localisation) of the game while keeping the message that it's a game able to engender passion in its staff. It's savvy marketing that is being used more and more frequently with varying success; most recently with <a href="http://www.sega.co.uk/platinumgames/bayonetta/?t=EnglishUK">Bayonetta</a> which keeps drip feeding information through, starting with a bizarre but fascinating <a href="http://platinumgames.com/2009/05/12/pgtv-episode-4-first-climax-trailer-commentary/">trailer commentary</a> from the game director Hideki Kamiya and continuing with <a href="http://platinumgames.com/2009/07/30/sound-design-in-bayonetta/">short blog entries</a> from various members of the team describing the creation of the game. Like the Aion videos it satiates the desire for information about the game, isn't wholly condescending to the audience and is quirky enough to be engaging.</p>
<p>All of this piqued my interest enough to consider buying the game, but with it still in closed beta, there was no free trial period or any suitably objective opinion on it; I then tried the next best thing and asked on a forum of people I knew well. The idea was met with disdain and general scorn, neatly summarised as a "Korean grind-fest" and discarded just as quickly; more in-depth opinions were that I would likely find it idiosyncratic enough not being an entrenched MMO player but the recommendation was to avoid. The time commitment was at the forefront of my mind: it wasn't as if I had a surplus of time on my hands or that I had expended all other things to do or play but something still nagged at me despite the opinions of people I trusted.</p>
<p>For a time I attempted to put it to the back of my mind - that rational part that calibrates and evaluates: high fantasy? I don't even like high fantasy! I was surely only interested in it for its looks, such superficial thoughts! Regardless, one Friday evening after a particularly eventful day at work, I capitulated and purchased the <a href="http://store.steampowered.com/app/29650/">Collector's Edition</a> that had been beckoning to me, siren-like, on <a href="http://store.steampowered.com/">Steam</a>. The tipping point for me was the thought that by trying to be "good" and not indulging in what is a genuine interest for me, would be denying myself. This is something that rattles around in my head a lot, mostly when it comes to games that I enjoy but aren't very good at (read: fighting games) and my usual justification for not buying them is that I rarely have the impetus to continuously improve when there are other games and pursuits that are more "worthwhile" of my time.</p>
<p>So did I do it because of the marketing? Because of the buzz? Or because of the aesthetics?</p>
{{ thumbnails([{ 'src': '2009/08/aionbreasts.jpg', 'actions': [ 'resize:lead' ], 'caption': 'Aion Breasts' }]) }}
<p>Regardless, I've decided to gratify my interest in time for the final closed beta this weekend (14th - 17th August) which should reveal whether I've made a drastic error or not. To that end I've decided to record at least some of my first steps into Aion as some kind of macabre memory of when I either first started on a path well trodden or of the folly of my less cynical decision. I'm certain that if anything, it'll be interesting.</p>
*/
