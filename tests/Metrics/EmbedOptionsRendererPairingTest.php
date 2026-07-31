<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Form\Embed\GaugeEmbedOptionsType;
use App\Form\Embed\TimeSeriesEmbedOptionsType;
use App\Metrics\Dto\Embed\EmbedOptionsFactory;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Metrics\Renderer\ChartDefaultsResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * EmbedOptionsFactory::formType() (does this renderer get a chart subform?) and
 * ChartDefaultsResolver::resolve() (what configuration feeds it) must agree renderer by
 * renderer, and the resolved configuration must be an allowed 'defaults' type for that
 * form: EmbedType::addChartOptions() pipes one straight into the other.
 */
class EmbedOptionsRendererPairingTest extends KernelTestCase
{
    public function testFormTypePresenceMatchesResolvedDefaultsAndFormTypesAccept(): void
    {
        self::bootKernel();

        $resolver = self::getContainer()->get(ChartDefaultsResolver::class);
        $this->assertInstanceOf(ChartDefaultsResolver::class, $resolver);

        // A hand-built factory carrying only the two chart form types, rather than the
        // container's real FormFactoryInterface: pulling in the whole DI-registered form
        // type registry drags in unrelated services that aren't configured in the test env.
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new TimeSeriesEmbedOptionsType())
            ->addType(new GaugeEmbedOptionsType())
            ->getFormFactory();

        // Any metric works: the pairing under test depends only on the renderer, and
        // ChartDefaultsResolver::resolve() still needs a Metric argument to call.
        $metric = Metric::SystemCpuUsage;

        foreach (Renderer::cases() as $renderer) {
            $formType = EmbedOptionsFactory::formType($renderer);
            $defaults = $resolver->resolve($metric, $renderer);

            $this->assertSame(
                $formType === null,
                $defaults === null,
                sprintf('EmbedOptionsFactory::formType() and ChartDefaultsResolver::resolve() disagree for %s.', $renderer->value),
            );

            if ($formType === null || $defaults === null) {
                continue;
            }

            // setAllowedTypes('defaults', ...) throws InvalidOptionsException if the resolved
            // configuration class isn't what that form type expects.
            $form = $formFactory->create($formType, null, ['defaults' => $defaults]);
            $this->assertInstanceOf(FormInterface::class, $form);
        }
    }
}
