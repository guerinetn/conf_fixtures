<?php

namespace App\Dumper;

use Symfony\Component\HttpKernel\Exception\InvalidMetadataException;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Dumper\DumperInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Metadata\MetadataStoreInterface;

class WorkflowDumper implements DumperInterface
{
    public const DIRECTION_TOP_TO_BOTTOM = 'TB';
    public const DIRECTION_TOP_DOWN = 'TD';
    public const DIRECTION_BOTTOM_TO_TOP = 'BT';
    public const DIRECTION_RIGHT_TO_LEFT = 'RL';
    public const DIRECTION_LEFT_TO_RIGHT = 'LR';

    private const VALID_DIRECTIONS = [
        self::DIRECTION_TOP_TO_BOTTOM,
        self::DIRECTION_TOP_DOWN,
        self::DIRECTION_BOTTOM_TO_TOP,
        self::DIRECTION_RIGHT_TO_LEFT,
        self::DIRECTION_LEFT_TO_RIGHT,
    ];

    private const VALID_STYLES_PLACE_KEY = [
        'bg_color',
        'color',
        'shape',
        'stroke-width',
        'stroke-color',
    ];

    private const VALID_STYLES_TRANSITION_KEY = [
        'color',
        'width',
        'text-color',
        'link-size', // one of standard / long /longer / longest
        'link-style', // one of line / dotted / thick
    ];

    public const TRANSITION_TYPE_STATEMACHINE = 'statemachine';
    public const TRANSITION_TYPE_WORKFLOW = 'workflow';

    private const VALID_TRANSITION_TYPES = [
        self::TRANSITION_TYPE_STATEMACHINE,
        self::TRANSITION_TYPE_WORKFLOW,
    ];

    private string $transitionType;
    private ?string $direction = null;
    private ?array $stylesPlace = null;
    private ?array $stylesTransition = null;
    private array $presentation;
    private array $ignorePlaces;
    private array $onlyPlaces;

    private ?MetadataStoreInterface $meta = null;
    /**
     * Just tracking the transition id is in some cases inaccurate to
     * get the link's number for styling purposes.
     */
    private int $linkCount = 0;

    public function __construct(
        string $transitionType,
        ?string $presentation,
        ?string $ignorePlaces,
        ?string $onlyPlaces,
    ) {
        $this->validateTransitionType($transitionType);

        $this->transitionType = $transitionType;
        if (null !== $onlyPlaces) {
            $this->onlyPlaces = explode(',', $onlyPlaces);
        } else {
            $this->presentation = (null !== $presentation) ? explode(',', $presentation) : [];
            $this->ignorePlaces = (null !== $ignorePlaces) ? explode(',', $ignorePlaces) : [];
        }
    }

    public function dump(Definition $definition, ?Marking $marking = null, array $options = []): string
    {
        $this->linkCount = 0;
        $this->meta = $definition->getMetadataStore();

        $this->validateAndStoreMetaWorkflow();

        $output = ['graph '.$this->direction];

        $placeNameMap = $this->addPlacesToOutput(
            $this->preparePlaces($definition),
            $definition,
            $marking,
            $output
        );
        $this->transformTransitionsAndAddToOutput($definition, $placeNameMap, $output);

        return implode("\n", $output);
    }

    private function preparePlaces(Definition $definition): array
    {
        $places = [];

        if (!empty($this->onlyPlaces)) {
            foreach ($definition->getTransitions() as $transition) {
                if (!empty(array_intersect($this->onlyPlaces, $transition->getFroms()))
                    || !empty(array_intersect($this->onlyPlaces, $transition->getTos()))) {
                    foreach (array_merge($transition->getTos(), $transition->getFroms()) as $placeToAdd) {
                        if (!isset($places[$placeToAdd])) {
                            $places[$placeToAdd] = $this->meta->getPlaceMetadata($placeToAdd);
                        }
                    }
                }
            }
        } else {
            foreach ($definition->getPlaces() as $place) {
                if (in_array($place, $this->ignorePlaces, true)) {
                    continue;
                }
                $placeMeta = $this->meta->getPlaceMetadata($place);
                if (!empty($this->presentation)
                    && ((isset($placeMeta['representation'])
                            && empty(array_intersect($this->presentation, $placeMeta['representation'])))
                        || !isset($placeMeta['representation']))
                ) {
                    continue;
                }

                $places[$place] = $placeMeta;
            }
        }

        return $places;
    }

    private function addPlacesToOutput(array $places, Definition $definition, ?Marking $marking, array &$output): array
    {
        $placeNameMap = [];
        $placeId = 0;
        foreach ($places as $place => $placeMeta) {
            [$placeNodeName, $placeNode, $placeStyle] = $this->preparePlace(
                $placeId,
                $place,
                $placeMeta,
                in_array($place, $definition->getInitialPlaces(), true),
                $marking?->has($place) ?? false,
            );

            $output[] = $placeNode;

            if ('' !== $placeStyle) {
                $output[] = $placeStyle;
            }

            $placeNameMap[$place] = $placeNodeName;

            ++$placeId;
        }

        return $placeNameMap;
    }

    private function transformTransitionsAndAddToOutput(Definition $definition, array $placeNameMap, array &$output): void
    {
        foreach ($definition->getTransitions() as $transitionId => $transition) {
            $transitionMeta = $this->meta->getTransitionMetadata($transition);
            $transitionLabel = $transitionMeta['label'] ?? $transition->getName();

            foreach ($transition->getFroms() as $from) {
                if (!isset($placeNameMap[$from])) {
                    continue;
                }
                $fromOutput = $placeNameMap[$from];

                foreach ($transition->getTos() as $to) {
                    if (!isset($placeNameMap[$to])) {
                        continue;
                    }

                    if (!empty($this->onlyPlaces)
                        && !in_array($from, $this->onlyPlaces, true)
                        && !in_array($to, $this->onlyPlaces, true)
                    ) {
                        continue;
                    }

                    $to = $placeNameMap[$to];

                    $transitionOutput = $this->styleTransition(
                        from: $fromOutput,
                        to: $to,
                        transitionId: $transitionId,
                        transitionLabel: $transitionLabel,
                        transitionMeta: $transitionMeta
                    );

                    foreach ($transitionOutput as $line) {
                        if (\in_array($line, $output, true)) {
                            if (0 < strpos($line, '-->')) {
                                --$this->linkCount;
                            }
                            continue;
                        }
                        $output[] = $line;
                    }
                }
            }
        }
    }

    private function styleTransition(string $from, string $to, int $transitionId, string $transitionLabel,
        array $transitionMeta,
    ): array {
        if (self::TRANSITION_TYPE_STATEMACHINE === $this->transitionType) {
            return $this->styleStateMachineTransition(
                from: $from,
                to: $to,
                transitionLabel: $transitionLabel,
                transitionMeta: $transitionMeta
            );
        }

        return $this->styleWorkflowTransition(
            from: $from,
            to: $to,
            transitionId: $transitionId,
            transitionLabel: $transitionLabel,
            transitionMeta: $transitionMeta
        );
    }

    private function preparePlace(
        int $placeId,
        string $placeName,
        array $meta,
        bool $isInitial,
        bool $hasMarking,
    ): array {
        $placeLabel = $placeName;
        if (\array_key_exists('label', $meta)) {
            $placeLabel = $this->meta['label'];
        }

        if (isset($meta['style'], $this->stylesPlace[$meta['style']])) {
            foreach (array_keys($this->stylesPlace[$meta['style']]) as $styleKey) {
                if (!array_key_exists($styleKey, $meta)) {
                    $meta[$styleKey] = $this->stylesPlace[$meta['style']][$styleKey];
                }
            }
        }

        $placeShape = 'circle';
        if ($isInitial) {
            $placeShape = 'stadium';
        }
        $placeShape = $meta['shape'] ?? $placeShape;

        $placeLabel = $this->escape($placeLabel);

        $labelShape = match ($placeShape) {
            'rounded' => '(%s)',
            'stadium' => '([%s])',
            'subroutine' => '[[%s]]',
            'cylindrical' => '[(%s)]',
            'circle' => '((%s))',
            'circle_double' => '(((%s)))',
            'asymmetric' => '>%s]',
            'rhombus' => '{%s}',
            'hexagon' => '{{%s}}',
            'parallelogram' => '[/%s/]',
            'parallelogram_alt' => '[\%s\]',
            'trapezoid' => '[/%s\]',
            'trapezoid_alt' => '[\%s/]',
        };

        $placeNodeName = 'place'.$placeId;
        $placeNodeFormat = '%s'.$labelShape;
        $placeNode = sprintf($placeNodeFormat, $placeNodeName, $placeLabel);

        $placeStyle = $this->styleNode($meta, $placeNodeName, $hasMarking);

        return [$placeNodeName, $placeNode, $placeStyle];
    }

    private function styleNode(array $meta, string $nodeName, bool $hasMarking = false): string
    {
        $nodeStyles = [];
        $styleMappings = [
            'bg_color' => 'fill',
            'color' => 'color',
            'stroke-width' => 'stroke-width',
            'stroke-color' => 'stroke',
        ];

        foreach ($styleMappings as $key => $keyStyle) {
            if (isset($meta[$key])) {
                $nodeStyles[] = $keyStyle.':'.$meta[$key];
            }
        }
        if ($hasMarking) {
            $nodeStyles[] = 'stroke-width:4px';
        }

        if (empty($nodeStyles)) {
            return '';
        }

        return sprintf('style %s %s', $nodeName, implode(',', $nodeStyles));
    }

    /**
     * Replace double quotes with the mermaid escape syntax and
     * ensure all other characters are properly escaped.
     */
    private function escape(string $label): string
    {
        $label = str_replace('"', '#quot;', $label);

        return sprintf('"%s"', $label);
    }

    public function validateDirection(string $direction): void
    {
        if (!\in_array($direction, self::VALID_DIRECTIONS, true)) {
            throw new InvalidArgumentException(sprintf('Direction "%s" is not valid, valid directions are: "%s".', $direction, implode(', ', self::VALID_DIRECTIONS)));
        }
    }

    private function validateTransitionType(string $transitionType): void
    {
        if (!\in_array($transitionType, self::VALID_TRANSITION_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Transition type "%s" is not valid, valid types are: "%s".', $transitionType, implode(', ', self::VALID_TRANSITION_TYPES)));
        }
    }

    private function styleStateMachineTransition(
        string $from,
        string $to,
        string $transitionLabel,
        array $transitionMeta,
    ): array {
        $transitionOutput = [
            sprintf(
                '%s%s|%s|%s',
                $from,
                match ($transitionMeta['link-size'] ?? 'standard') {
                    'long' => match ($transitionMeta['link-style'] ?? 'line') {
                        'dotted' => '-..->',
                        'thick' => '===>',
                        default => '--->',
                    },
                    'longer' => match ($transitionMeta['link-style'] ?? 'line') {
                        'dotted' => '-...->',
                        'thick' => '====>',
                        default => '---->',
                    },
                    'longest' => match ($transitionMeta['link-style'] ?? 'line') {
                        'dotted' => '-.....->',
                        'thick' => '======>',
                        default => '------>',
                    },
                    default => match ($transitionMeta['link-style'] ?? 'line') {
                        'dotted' => '-.->',
                        'thick' => '==>',
                        default => '-->',
                    },
                },
                str_replace("\n", ' ', $this->escape($transitionLabel)), $to
            ),
        ];

        $linkStyle = $this->styleLink($transitionMeta);
        if ('' !== $linkStyle) {
            $transitionOutput[] = $linkStyle;
        }

        ++$this->linkCount;

        return $transitionOutput;
    }

    private function styleWorkflowTransition(
        string $from,
        string $to,
        int $transitionId,
        string $transitionLabel,
        array $transitionMeta,
    ): array {
        $transitionOutput = [];

        $transitionLabel = $this->escape($transitionLabel);
        $transitionNodeName = 'transition'.$transitionId;

        $transitionOutput[] = sprintf('%s[%s]', $transitionNodeName, $transitionLabel);

        $transitionNodeStyle = $this->styleNode($transitionMeta, $transitionNodeName);
        if ('' !== $transitionNodeStyle) {
            $transitionOutput[] = $transitionNodeStyle;
        }

        $connectionStyle = '%s-->%s';
        $transitionOutput[] = sprintf($connectionStyle, $from, $transitionNodeName);

        $linkStyle = $this->styleLink($transitionMeta);
        if ('' !== $linkStyle) {
            $transitionOutput[] = $linkStyle;
        }

        ++$this->linkCount;

        $transitionOutput[] = sprintf($connectionStyle, $transitionNodeName, $to);

        $linkStyle = $this->styleLink($transitionMeta);
        if ('' !== $linkStyle) {
            $transitionOutput[] = $linkStyle;
        }

        ++$this->linkCount;

        return $transitionOutput;
    }

    private function styleLink(array $transitionMeta): string
    {
        $linkStyles = [];

        if (isset($transitionMeta['style'], $this->stylesTransition[$transitionMeta['style']])) {
            foreach (array_keys($this->stylesTransition[$transitionMeta['style']]) as $styleKey) {
                if (!array_key_exists($styleKey, $transitionMeta)) {
                    $transitionMeta[$styleKey] = $this->stylesTransition[$transitionMeta['style']][$styleKey];
                }
            }
        }

        if (\array_key_exists('color', $transitionMeta)) {
            $linkStyles[] = sprintf(
                'stroke:%s',
                $transitionMeta['color']
            );
            if (!\array_key_exists('text-color', $transitionMeta)) {
                $linkStyles[] = sprintf(
                    'color:%s',
                    $transitionMeta['color']
                );
            }
        }

        if (\array_key_exists('text-color', $transitionMeta)) {
            $linkStyles[] = sprintf(
                'color:%s',
                $transitionMeta['text-color']
            );
        }

        if (\array_key_exists('width', $transitionMeta)) {
            $linkStyles[] = sprintf(
                'stroke-width:%s',
                $transitionMeta['width']
            );
        }
        if (0 === count($linkStyles)) {
            return '';
        }

        return sprintf('linkStyle %d %s', $this->linkCount, implode(',', $linkStyles));
    }

    private function validateAndStoreMetaWorkflow(): void
    {
        $workflowMeta = $this->meta->getWorkflowMetadata();

        $direction = $workflowMeta['schema']['direction'] ?? self::DIRECTION_LEFT_TO_RIGHT;
        $this->validateDirection($direction);
        $this->direction = $direction;

        if (\array_key_exists('styles', $workflowMeta)) {
            $workflowMeta = $workflowMeta['styles'];
            if (\array_key_exists('places', $workflowMeta)) {
                foreach ($workflowMeta['places'] as $metaStyle) {
                    foreach (array_keys($metaStyle) as $key) {
                        if (!in_array($key, self::VALID_STYLES_PLACE_KEY, true)) {
                            throw new InvalidMetadataException(sprintf('key "%s" is not valid, valid keys are: "%s".', $key, implode(', ', self::VALID_STYLES_PLACE_KEY)));
                        }
                    }
                }
                $this->stylesPlace = $workflowMeta['places'];
            }

            if (\array_key_exists('transitions', $workflowMeta)) {
                foreach ($workflowMeta['transitions'] as $metaStyle) {
                    foreach (array_keys($metaStyle) as $key) {
                        if (!in_array($key, self::VALID_STYLES_TRANSITION_KEY, true)) {
                            throw new InvalidMetadataException(sprintf('key "%s" is not valid, valid keys are: "%s".', $key, implode(', ', self::VALID_STYLES_TRANSITION_KEY)));
                        }
                    }
                }
                $this->stylesTransition = $workflowMeta['transitions'];
            }
        }
    }
}
