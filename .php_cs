<?php

return PhpCsFixer\Config::create()
    ->setRules([
        'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['align_double_arrow' => false, 'align_equals' => false],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => true,
        'braces' => ['allow_single_line_closure' => false],
        'cast_spaces' => ['space' => 'single'],
        'class_definition' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'single'],
        'elseif' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'function_typehint_space' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'access']],
        'include' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_cast' => true,
        'lowercase_constants' => true,
        'lowercase_keywords' => true,
        'magic_constant_casing' => true,
        'method_argument_space' => ['ensure_fully_multiline' => true, 'keep_multiple_spaces_after_comma' => false],
        'method_separation' => true,
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_closing_tag' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_consecutive_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'no_short_bool_cast' => true,
        'no_short_echo_tag' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_after_function_name' => true,
        'no_spaces_inside_parenthesis' => true,
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'not_operator_with_space' => true,
        'not_operator_with_successor_space' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'pre_increment' => true,
        'return_type_declaration' => true,
        'self_accessor' => true,
        'semicolon_after_instruction' => true,
        'short_scalar_cast' => true,
        'single_blank_line_at_eof' => true,
        'single_blank_line_before_namespace' => true,
        'single_class_element_per_statement' => true,
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'single_quote' => true,
        'space_after_semicolon' => true,
        'standardize_not_equals' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline_array' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'visibility_required' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->exclude('vendor')
        ->in(__DIR__)
    )
;

/*
This document has been generated with
https://mlocati.github.io/php-cs-fixer-configurator/
you can change this configuration by importing this YAML code:

fixers:
  align_multiline_comment:
    comment_type: phpdocs_like
  array_syntax:
    syntax: short
  binary_operator_spaces:
    align_double_arrow: false
    align_equals: false
  blank_line_after_namespace: true
  blank_line_after_opening_tag: true
  blank_line_before_statement: true
  braces:
    allow_single_line_closure: false
  cast_spaces:
    space: single
  class_definition: true
  concat_space:
    spacing: one
  declare_equal_normalize:
    space: single
  elseif: true
  encoding: true
  full_opening_tag: true
  function_declaration:
    closure_function_spacing: one
  function_typehint_space: true
  general_phpdoc_annotation_remove:
    annotations:
      - author
      - access
  include: true
  indentation_type: true
  line_ending: true
  linebreak_after_opening_tag: true
  lowercase_cast: true
  lowercase_constants: true
  lowercase_keywords: true
  magic_constant_casing: true
  method_argument_space:
    ensure_fully_multiline: true
    keep_multiple_spaces_after_comma: false
  method_separation: true
  native_function_casing: true
  new_with_braces: true
  no_blank_lines_after_class_opening: true
  no_blank_lines_after_phpdoc: true
  no_closing_tag: true
  no_empty_comment: true
  no_empty_phpdoc: true
  no_empty_statement: true
  no_extra_consecutive_blank_lines: true
  no_leading_import_slash: true
  no_leading_namespace_whitespace: true
  no_mixed_echo_print:
    use: echo
  no_multiline_whitespace_around_double_arrow: true
  no_multiline_whitespace_before_semicolons: true
  no_short_bool_cast: true
  no_short_echo_tag: true
  no_singleline_whitespace_before_semicolons: true
  no_spaces_after_function_name: true
  no_spaces_inside_parenthesis: true
  no_trailing_comma_in_list_call: true
  no_trailing_comma_in_singleline_array: true
  no_trailing_whitespace: true
  no_trailing_whitespace_in_comment: true
  no_unneeded_control_parentheses: true
  no_unused_imports: true
  no_useless_else: true
  no_useless_return: true
  no_whitespace_before_comma_in_array: true
  no_whitespace_in_blank_line: true
  normalize_index_brace: true
  not_operator_with_space: true
  not_operator_with_successor_space: true
  object_operator_without_whitespace: true
  ordered_imports: true
  phpdoc_add_missing_param_annotation:
    only_untyped: false
  phpdoc_annotation_without_dot: true
  phpdoc_indent: true
  phpdoc_no_access: true
  phpdoc_no_alias_tag: true
  phpdoc_no_empty_return: true
  phpdoc_no_package: true
  phpdoc_no_useless_inheritdoc: true
  phpdoc_order: true
  phpdoc_return_self_reference: true
  phpdoc_scalar: true
  phpdoc_separation: true
  phpdoc_single_line_var_spacing: true
  phpdoc_summary: true
  phpdoc_to_comment: true
  phpdoc_trim: true
  phpdoc_types: true
  phpdoc_var_without_name: true
  pre_increment: true
  return_type_declaration: true
  self_accessor: true
  semicolon_after_instruction: true
  short_scalar_cast: true
  single_blank_line_at_eof: true
  single_blank_line_before_namespace: true
  single_class_element_per_statement: true
  single_import_per_statement: true
  single_line_after_imports: true
  single_quote: true
  space_after_semicolon: true
  standardize_not_equals: true
  switch_case_semicolon_to_colon: true
  switch_case_space: true
  ternary_operator_spaces: true
  trailing_comma_in_multiline_array: true
  trim_array_spaces: true
  unary_operator_spaces: true
  visibility_required: true
  whitespace_after_comma_in_array: true

*/