<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Render\Html;

use InvalidArgumentException;
use Nelmio\ApiDocBundle\Render\OpenApiRenderer;
use Nelmio\ApiDocBundle\Render\RenderOpenApi;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Server;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Twig\Environment;
use Twig\TwigFunction;

class HtmlOpenApiRenderer implements OpenApiRenderer
{
    /**
     * @var Environment|\Twig_Environment
     */
    private $twig;
    /**
     * @var AssetExtension
     */
    private $assetExtension;

    public function __construct($twig, AssetExtension $assetExtension)
    {
        if (!$twig instanceof \Twig_Environment && !$twig instanceof Environment) {
            throw new InvalidArgumentException(sprintf('Providing an instance of "%s" as twig is not supported.', get_class($twig)));
        }
        $this->twig = $twig;
        $this->assetExtension = $assetExtension;
    }

    public function getFormat(): string
    {
        return RenderOpenApi::HTML;
    }

    public function render(OpenApi $spec, array $options = []): string
    {
        $options += [
            'server_url' => null,
            'assets_mode' => AssetsMode::CDN,
            'swagger_ui_config' => [],
        ];

        $this->twig->addFunction(
            new TwigFunction(
                'nelmioAsset',
                new GetNelmioAsset($this->assetExtension, $options['assets_mode'])
            )
        );

        return $this->twig->render(
            '@NelmioApiDoc/SwaggerUi/index.html.twig',
            [
                'swagger_data' => ['spec' => $this->createJsonSpec($spec, $options['server_url'])],
                'assets_mode' => $options['assets_mode'],
                'swagger_ui_config' => $options['swagger_ui_config'],
            ]
        );
    }

    private function createJsonSpec(OpenApi $spec, $serverUrl)
    {
        if ($serverUrl) {
            $spec->servers = [new Server(['url' => $serverUrl])];
        }

        return json_decode($spec->toJson(), true);
    }
}
