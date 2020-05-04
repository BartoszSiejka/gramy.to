<?php

/*
 * This file is part of the gramy.to (weplay.it) app.
 *
 * (c) Bartosz Siejka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ManageController extends AbstractController
{
    public function index()
    {
        return $this->render('manage.html.twig', array());
    }
}