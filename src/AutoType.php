<?php


namespace MdNetdesign\Form;


use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutoType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options) {
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
      $formFieldGroup = $formField->getGroup();
      if (in_array($formFieldGroup, $nonWildcardGroups, true) || $formFieldGroup === FormField::GROUP_ALL)
        return true;

      foreach ($wildcardGroups as $wildcardGroup)
        if (str_starts_with($wildcardGroup, "*")) {
          if (str_ends_with($formFieldGroup, str_replace("*", "", $wildcardGroup)))
            return true;
        } else {
          // ends with *
          if (str_starts_with($formFieldGroup, str_replace("*", "", $wildcardGroup)))
            return true;
        }

      return false;
    };

    try {
      $rClass = new ReflectionClass($options["data_class"] ?? $options["reflection_class"]);

      /** @var FormField[] $formFields */
      $formFields = array_filter(array_map(fn(ReflectionAttribute $rAttribute) => $rAttribute->newInstance(), $rClass->getAttributes(FormField::class)), $filterFormField);
      foreach ($formFields as $formField) {
        if ($formField->getName() === null)
          throw new LogicException("Form field on class '{$rClass->getName()}' must have a custom name.");

        $builder->add($formField->getName(), $formField->getType(), $formField->getOptions());
      }

      foreach ($rClass->getProperties() as $rProperty) {
        /** @var FormField[] $formFields */
        $formFields = array_filter(array_map(fn(ReflectionAttribute $rAttribute) => $rAttribute->newInstance(), $rProperty->getAttributes(FormField::class)), $filterFormField);

        if (($sizeOfFormFields = sizeof($formFields)) === 0)
          continue;
        if ($sizeOfFormFields > 1)
          throw new LogicException("Property '{$rProperty->getName()}' can only be editable once in groups '".join(",", $groups)."'.");

        $formField = reset($formFields);

        if ($formField->getName() !== null)
          throw new LogicException("Form field on property '{$rProperty->getName()}' must not have a custom name, only class level form fields can use this property.");

        $builder->add(($propertyName = $rProperty->getName()), $formField->getType(), $formField->getOptions());

        if (($dataTransformer = $formField->getDataTransformer()) !== null) {
          if (!is_subclass_of($dataTransformer, DataTransformerInterface::class))
            throw new LogicException("$dataTransformer must implement ".DataTransformerInterface::class.".");

          $builder->get($propertyName)
            ->addModelTransformer(new $dataTransformer);
        }
      }
    } catch (ReflectionException $e) {
      throw new LogicException($e->getMessage(), previous: $e);
    }
  }

  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefault("group", null);
    $resolver->setAllowedTypes("group", ["null", "string"]);

    $resolver->setDefault("groups", null);
    $resolver->setAllowedTypes("groups", ["null", "array"]);

    $resolver->setDefault("reflection_class", null);
    $resolver->setAllowedTypes("reflection_class", ["null", "string"]);
  }

}