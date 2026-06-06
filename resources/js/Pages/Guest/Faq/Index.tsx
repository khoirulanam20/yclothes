import { Head } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { CmsHtmlContent } from '@/cms/CmsHtmlContent';
import { PageContainer } from '@/components/storefront/PageContainer';
import { SectionCard } from '@/components/storefront/SectionCard';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

type FaqItem = { id: number; question: string; answer: string };
type FaqCategory = { id: number; name: string; items: FaqItem[] };
type Props = { faqCategories: FaqCategory[] };

export default function Index({ faqCategories }: Props) {
    return (
        <GuestLayout>
            <Head title="FAQ" />
            <PageContainer narrow>
                <SectionCard title="Pertanyaan Umum">
                    <div className="space-y-6">
                        {faqCategories.map((cat) => (
                            <section key={cat.id}>
                                <h2 className="text-sm font-semibold text-primary mb-3">{cat.name}</h2>
                                <Accordion type="single" collapsible className="w-full">
                                    {cat.items.map((item) => (
                                        <AccordionItem key={item.id} value={`item-${item.id}`}>
                                            <AccordionTrigger className="text-sm text-left">
                                                {item.question}
                                            </AccordionTrigger>
                                            <AccordionContent>
                                                <CmsHtmlContent html={item.answer} bare className="text-muted-foreground" />
                                            </AccordionContent>
                                        </AccordionItem>
                                    ))}
                                </Accordion>
                            </section>
                        ))}
                    </div>
                </SectionCard>
            </PageContainer>
        </GuestLayout>
    );
}
