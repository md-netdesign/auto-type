<?php


namespace MdNetdesign\AutoType;


use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

class AutoType extends AbstractType
{

  private readonly Traversable $dataTransformersIterator;
  private ?array $dataTransformers = null;

  public function __construct(
    #[TaggedIterator("auto_type.data_transformer")] Traversable $dataTransformers
  ) {
    $this->dataTransformersIterator = $dataTransformers;
  }

  public function buildForm(FormBuilderInterface $builder, array $options): void {
    ["group" => $group, "groups" => $groups] = $options;

    if ($group === null && empty($groups))
      throw new LogicException("Either option 'group' or 'groups' must be set.");

    if ($group !== null && !empty($groups))
      throw new LogicException("Option 'group' and 'groups' cannot both be set.");

    $groups = empty($groups) ? [$group] : $groups;

    if (in_array(FormField::GROUP_ALL, $groups))
      $groups[] = "*";

    $wildcardGroups = array_filter($groups, fn(string $group) => str_starts_with($group, "*") || str_ends_with($group, "*"));
    $nonWildcardGroups = array_diff($groups, $wildcardGroups);

    foreach ($nonWildcardGroups as $group)
      if (str_contains($group, "*"))
        throw new LogicException("Group name '$group' must not contain '*'.");

    $filterFormField = function (FormField $formField) use ($nonWildcardGroups, $wildcardGroups) {
      if (in_array($formField->group, $nonWildcardGroups, true) || $formField->group === FormField::GROUP_ALL)
        return true;

      foreach ($wildcardGroups as $wildcardGroup)
        if (str_starts_with($wildcardGroup, "*")) {
          if (str_ends_with($formField->group, str_replace("*", "", $wildcardGroup)))
            return true;
        } else {
          // ends with *
          if (str_starts_with($formField->group, str_replace("*", "", $wildcardGroup)))
            return true;
        }

      return false;
    };

    try {
      $rClass = new ReflectionClass($options["data_class"] ?? $options["reflection_class"]);

      /** @var FormField[] $formFields */
      $formFields = array_filter(array_map(fn(ReflectionAttribute $rAttribute) => $rAttribute->newInstance(), $rClass->getAttributes(FormField::class)), $filterFormField);
      foreach ($formFields as $formField) {
        if ($formField->name === null)
          throw new LogicException("Form field on class '{$rClass->getName()}' must have a custom name.");

        $builder->add($formField->name, $formField->type, $formField->options);
      }

      foreach ($rClass->getProperties() as $rProperty) {
        /** @var FormField[] $formFields */
        $formFields = array_filter(array_map(fn(ReflectionAttribute $rAttribute) => $rAttribute->newInstance(), $rProperty->getAttributes(FormField::class)), $filterFormField);

        if (($sizeOfFormFields = sizeof($formFields)) === 0)
          continue;
        if ($sizeOfFormFields > 1)
          throw new LogicException("Property '{$rProperty->getName()}' can only be editable once in groups '".join(",", $groups)."'.");

        $formField = reset($formFields);

        if ($formField->name !== null)
          throw new LogicException("Form field on property '{$rProperty->getName()}' must not have a custom name, only class level form fields can use this property.");

        $builder->add(($propertyName = $rProperty->getName()), $formField->type, $formField->options);

        if ($formField->dataTransformer !== null) {
          if (!is_subclass_of($formField->dataTransformer, DataTransformerInterface::class))
            throw new LogicException("$formField->dataTransformer must implement ".DataTransformerInterface::class.".");

          if ($this->dataTransformers === null)
            $this->dataTransformers = array_column(array_map(fn(DataTransformerInterface $dataTransformer) => [$dataTransformer::class, $dataTransformer], iterator_to_array($this->dataTransformersIterator)), 1, 0);

          if (array_key_exists($formField->dataTransformer, $this->dataTransformers))
            $builder->get($propertyName)
              ->addModelTransformer($this->dataTransformers[$formField->dataTransformer]);
          else
            $builder->get($propertyName)
              ->addModelTransformer(new $formField->dataTransformer);
        }
      }
    } catch (ReflectionException $e) {
      throw new LogicException($e->getMessage(), previous: $e);
    }
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefault("group", null);
    $resolver->setAllowedTypes("group", ["null", "string"]);

    $resolver->setDefault("groups", null);
    $resolver->setAllowedTypes("groups", ["null", "array"]);

    $resolver->setDefault("reflection_class", null);
    $resolver->setAllowedTypes("reflection_class", ["null", "string"]);
  }

}