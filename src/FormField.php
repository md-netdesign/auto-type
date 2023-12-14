<?php


namespace MdNetdesign\AutoType;


use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class FormField
{

  public const GROUP_ALL = "group-all";

  public function __construct(
    public readonly string  $group,
    public readonly string  $type,
    public readonly array   $options = [],
    public readonly ?string $name = null,
    public readonly ?array  $data = null,
    public readonly ?string $dataTransformer = null
  ) {
  }

  /** @deprecated Using $formField->getGroup() is deprecated, use direct readonly property access instead: $formField->group. */
  public function getGroup(): string {
    trigger_deprecation("md-netdesign/auto-type", "2.0.1", "Using \$formField->getGroup() is deprecated, use direct readonly property access instead: \$formField->group.");
    return $this->group;
  }

  /** @deprecated Using $formField->getType() is deprecated, use direct readonly property access instead: $formField->type. */
  public function getType(): string {
    trigger_deprecation("md-netdesign/auto-type", "2.0.1", "Using \$formField->getType() is deprecated, use direct readonly property access instead: \$formField->type.");
    return $this->type;
  }

  /** @deprecated Using $formField->getOptions() is deprecated, use direct readonly property access instead: $formField->options. */
  public function getOptions(): array {
    trigger_deprecation("md-netdesign/auto-type", "2.0.1", "Using \$formField->getOptions() is deprecated, use direct readonly property access instead: \$formField->options.");
    return $this->options;
  }

  /** @deprecated Using $formField->getName() is deprecated, use direct readonly property access instead: $formField->name. */
  public function getName(): ?string {
    trigger_deprecation("md-netdesign/auto-type", "2.0.1", "Using \$formField->getName() is deprecated, use direct readonly property access instead: \$formField->name.");
    return $this->name;
  }

  /** @deprecated Using $formField->getData() is deprecated, use direct readonly property access instead: $formField->data. */
  public function getData(): ?array {
    trigger_deprecation("md-netdesign/auto-type", "2.0.1", "Using \$formField->getData() is deprecated, use direct readonly property access instead: \$formField->data.");
    return $this->data;
  }

  /** @deprecated Using $formField->getDataTransformer() is deprecated, use direct readonly property access instead: $formField->dataTransformer. */
  public function getDataTransformer(): ?string {
    trigger_deprecation("md-netdesign/auto-type", "2.0.1", "Using \$formField->getDataTransformer() is deprecated, use direct readonly property access instead: \$formField->dataTransformer.");
    return $this->dataTransformer;
  }

}