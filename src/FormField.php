<?php


namespace MdNetdesign\AutoType;


use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class FormField
{

  public const GROUP_ALL = "group-all";

  public function __construct(
    private string  $group,
    private string  $type,
    private array   $options = [],
    private ?string $name = null,
    private ?array  $data = null,
    private ?string $dataTransformer = null
  ) {
  }

  public function getGroup(): string {
    return $this->group;
  }

  public function getType(): string {
    return $this->type;
  }

  public function getOptions(): array {
    return $this->options;
  }

  public function getName(): ?string {
    return $this->name;
  }

  public function getData(): ?array {
    return $this->data;
  }

  public function getDataTransformer(): ?string {
    return $this->dataTransformer;
  }

}