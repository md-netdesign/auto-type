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
    $group = $options["group"];
    try {
      $rClass = new ReflectionClass($options["data_class"] ?? $options["reflection_class"]);

      /** @var FormField[] $formFields */
      $formFields = array_filter(array_map(fn(ReflectionAttribute $rAttribute) => $rAttribute->newInstance(), $rClass->getAttributes(FormField::class)), fn(FormField $formField) => $formField->getGroup() === $group || $formField->getGroup() === FormField::GROUP_ALL);
      foreach ($formFields as $formField) {
        if ($formField->getName() === null)
          throw new LogicException("Form field on class '{$rClass->getName()}' must have a custom name.");

        $builder->add($formField->getName(), $formField->getType(), $formField->getOptions());
      }

      foreach ($rClass->getProperties() as $rProperty) {
        /** @var FormField[] $formFields */
        $formFields = array_filter(array_map(fn(ReflectionAttribute $rAttribute) => $rAttribute->newInstance(), $rProperty->getAttributes(FormField::class)), fn(FormField $formField) => $formField->getGroup() === $group || $formField->getGroup() === FormField::GROUP_ALL);

        if (($sizeOfFormFields = sizeof($formFields)) === 0)
          continue;
        if ($sizeOfFormFields > 1)
          throw new LogicException("Property '{$rProperty->getName()}' can only be editable once in group '$group'.");

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
    $resolver->setAllowedTypes("group", "string");

    $resolver->setDefault("reflection_class", null);
    $resolver->setAllowedTypes("reflection_class", ["null", "string"]);
  }

}